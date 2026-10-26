<?php

namespace App\Services;

use App\Models\ConvectionAsset;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSheetService
{
    public const DEFAULT_TIMEOUT = 30;
    public const BATCH_TIMEOUT = 90;

    public function getWebhookUrl(): ?string
    {
        $url = Setting::get('google_sheet_webhook_url');

        return ! empty($url) ? trim($url) : null;
    }

    public function isEnabled(): bool
    {
        return ! empty($this->getWebhookUrl());
    }

    public function isAutoSync(): bool
    {
        return $this->isEnabled() && Setting::get('google_sheet_auto_sync', '1') === '1';
    }

    /**
     * Test connection to Google Apps Script Webhook.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(?string $webhookUrl = null): array
    {
        $url = $webhookUrl ? trim($webhookUrl) : $this->getWebhookUrl();

        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'URL Webhook Google Sheets belum diisi.',
            ];
        }

        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT)
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            // Periksa jika Google mengembalikan 404 (File tidak ditemukan / deployment belum aktif)
            if (str_contains($response->body(), 'Halaman Tidak Ditemukan') || str_contains($response->body(), 'tidak ada')) {
                return [
                    'success' => false,
                    'message' => 'URL Webhook tidak ditemukan di Google (Error 404). Pastikan Web App sudah di-deploy dengan benar, hak akses diatur ke "Anyone" (Siapa saja), dan URL berakhiran /exec.',
                ];
            }

            // Harus benar-benar JSON dari skrip kita. Kalau deployment aksesnya
            // bukan "Anyone", Google membalas HTTP 200 berisi halaman login —
            // dulu itu lolos sebagai "berhasil" padahal tidak ada yang tersambung.
            if ($response->successful()) {
                $body = $response->json();
                $isOk = is_array($body) && (($body['status'] ?? '') === 'success' || ($body['success'] ?? false));

                if ($isOk) {
                    Setting::set('google_sheet_last_connected_at', now()->toIso8601String());
                    $this->rememberScriptInfo($body);

                    return [
                        'success' => true,
                        'message' => $body['message'] ?? 'Koneksi ke Google Sheets berhasil!',
                    ];
                }

                if (! is_array($body)) {
                    return [
                        'success' => false,
                        'message' => 'URL merespons, tapi bukan dari skrip Zada Karya — biasanya karena '
                            .'deployment-nya belum diatur "Who has access: Anyone", sehingga Google '
                            .'mengembalikan halaman login. Periksa juga apakah URL-nya berakhiran /exec.',
                    ];
                }

                return [
                    'success' => false,
                    'message' => $body['message'] ?? 'Skrip membalas, tapi statusnya bukan success.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Respon gagal dari Google Sheets (HTTP '.$response->status().').',
            ];
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'timed out')) {
                $friendly = 'Waktu koneksi habis (Timeout setelah '.self::DEFAULT_TIMEOUT.' detik). URL Webhook tidak merespons. Pastikan URL benar, deployment aktif, dan berakhiran /exec.';
            } else {
                $friendly = 'Koneksi gagal: '.$msg;
            }

            return [
                'success' => false,
                'message' => $friendly,
            ];
        }
    }

    /**
     * Trigger sheet formatting and setup on Google Sheets.
     *
     * @return array{success: bool, message: string}
     */
    public function setupSheet(?string $webhookUrl = null): array
    {
        $url = $webhookUrl ? trim($webhookUrl) : $this->getWebhookUrl();

        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'URL Webhook Google Sheets belum diatur.',
            ];
        }

        $res = $this->sendPayload(['action' => 'setup'], $url, self::BATCH_TIMEOUT);

        if ($res['success']) {
            return [
                'success' => true,
                'message' => $res['data']['message'] ?? 'Setup struktur tab dan dashboard berhasil dilakukan di Google Sheets!',
            ];
        }

        return [
            'success' => false,
            'message' => $res['error'] ?? 'Gagal menjalankan setup sheet.',
        ];
    }

    /**
     * Synchronize all orders, costs, and payments to Google Sheets in one batch.
     *
     * @return array{success: bool, message: string, counts?: array<string, int>}
     */
    public function syncAll(?string $webhookUrl = null): array
    {
        $url = $webhookUrl ? trim($webhookUrl) : $this->getWebhookUrl();

        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'URL Webhook Google Sheets belum diatur.',
            ];
        }

        $orders = Order::with(['customer', 'items', 'costs', 'payments'])
            ->latest('created_at')
            ->get();

        $costs = OrderCost::with(['order.customer', 'recorder'])
            ->latest('spent_at')
            ->latest('id')
            ->get();

        $payments = Payment::with(['order.customer', 'invoice'])
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $expenses = OperationalExpense::with('recorder')
            ->latest('expense_date')
            ->latest('id')
            ->get();

        $loans = Loan::with(['order.customer', 'payments.recorder', 'recorder'])
            ->latest('loan_date')
            ->latest('id')
            ->get();

        $loanPayments = LoanPayment::with('loan')
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $assets = ConvectionAsset::with('recorder')
            ->latest('purchase_date')
            ->latest('id')
            ->get();

        $formattedOrders = $orders->map(fn (Order $order) => $this->formatOrderData($order))->all();
        $formattedCosts = $costs->map(fn (OrderCost $cost) => $this->formatCostData($cost))->all();
        $formattedPayments = $payments->map(fn (Payment $payment) => $this->formatPaymentData($payment))->all();
        $formattedExpenses = $expenses->map(fn (OperationalExpense $expense) => $this->formatExpenseData($expense))->all();
        $formattedLoans = $loans->map(fn (Loan $loan) => $this->formatLoanData($loan))->all();
        $formattedLoanPayments = $loanPayments->map(fn (LoanPayment $payment) => $this->formatLoanPaymentData($payment))->all();
        $formattedAssets = $assets->map(fn (ConvectionAsset $asset) => $this->formatAssetData($asset))->all();

        $payload = [
            'action' => 'sync_all',
            'orders' => $formattedOrders,
            'costs' => $formattedCosts,
            'payments' => $formattedPayments,
            'expenses' => $formattedExpenses,
            'loans' => $formattedLoans,
            'loan_payments' => $formattedLoanPayments,
            'assets' => $formattedAssets,
            'synced_at' => now()->translatedFormat('d F Y H:i:s'),
        ];

        $res = $this->sendPayload($payload, $url, self::BATCH_TIMEOUT);

        if ($res['success']) {
            Setting::set('google_sheet_last_synced_at', now()->toIso8601String());

            return [
                'success' => true,
                'message' => 'Semua data berhasil disinkronkan ke Google Sheets.',
                'counts' => [
                    'orders' => $orders->count(),
                    'costs' => $costs->count(),
                    'payments' => $payments->count(),
                    'expenses' => $expenses->count(),
                    'loans' => $loans->count(),
                    'loan_payments' => $loanPayments->count(),
                    'assets' => $assets->count(),
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $res['error'] ?? 'Gagal menyinkronkan data ke Google Sheets.',
        ];
    }

    /**
     * Upsert a single order into Google Sheets.
     */
    public function syncOrder(Order $order): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $order->loadMissing(['customer', 'items', 'costs', 'payments']);

        return $this->send([
            'type' => 'order',
            'order' => $this->formatOrderData($order),
        ]);
    }

    /**
     * Add a single cost entry in Google Sheets and update parent order.
     */
    public function syncCost(OrderCost $cost, string $action = 'add'): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $cost->loadMissing(['order.customer', 'recorder']);

        $ok = $action === 'add'
            ? $this->send(['type' => 'cost', 'cost' => $this->formatCostData($cost)])
            : $this->send(['action' => 'delete', 'entity' => 'cost', 'id' => 'CST-'.$cost->id]);

        // Baris pesanan selalu disegarkan supaya Total HPP dan Estimasi Laba ikut berubah.
        if ($cost->order) {
            $this->syncOrder($cost->order->fresh(['customer', 'items', 'costs', 'payments']));
        }

        return $ok;
    }

    /**
     * Add a single payment entry in Google Sheets and update parent order.
     */
    public function syncPayment(Payment $payment, string $action = 'add'): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $payment->loadMissing(['order.customer', 'invoice']);

        $ok = $action === 'add'
            ? $this->send(['type' => 'payment', 'payment' => $this->formatPaymentData($payment)])
            : $this->send(['action' => 'delete', 'entity' => 'payment', 'id' => 'PAY-'.$payment->id]);

        // Baris pesanan selalu disegarkan supaya DP, Pelunasan, dan Sisa Tagihan ikut berubah.
        if ($payment->order) {
            $this->syncOrder($payment->order->fresh(['customer', 'items', 'costs', 'payments']));
        }

        return $ok;
    }

    /**
     * Hapus baris pesanan beserta seluruh biaya dan pembayarannya dari sheet.
     * Dipanggil SEBELUM record dihapus dari database, selagi id-nya masih ada.
     */
    public function deleteOrder(Order $order): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        return $this->send([
            'action' => 'delete',
            'entity' => 'order',
            'id' => 'ORD-'.$order->id,
            'cost_ids' => $order->costs->map(fn ($c) => 'CST-'.$c->id)->values()->all(),
            'payment_ids' => $order->payments->map(fn ($p) => 'PAY-'.$p->id)->values()->all(),
        ]);
    }

    /**
     * Add or delete a single operational expense entry in Google Sheets.
     */
    public function syncExpense(OperationalExpense $expense, string $action = 'add'): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $expense->loadMissing('recorder');

        return $action === 'add'
            ? $this->send(['type' => 'expense', 'expense' => $this->formatExpenseData($expense)])
            : $this->send(['action' => 'delete', 'entity' => 'expense', 'id' => $expense->reference_no ?: ('EXP-'.$expense->id)]);
    }

    /**
     * Add, update, or delete a single loan entry in Google Sheets.
     */
    public function syncLoan(Loan $loan, string $action = 'add'): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $loan->loadMissing(['order.customer', 'recorder', 'payments']);

        return $action === 'add'
            ? $this->send(['type' => 'loan', 'loan' => $this->formatLoanData($loan)])
            : $this->send(['action' => 'delete', 'entity' => 'loan', 'id' => $loan->reference_no ?: ('LOAN-'.$loan->id)]);
    }

    /**
     * Add or delete a single loan repayment entry in Google Sheets.
     */
    public function syncLoanPayment(LoanPayment $payment, string $action = 'add'): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $payment->loadMissing(['loan', 'recorder']);

        $ok = $action === 'add'
            ? $this->send(['type' => 'loan_payment', 'loan_payment' => $this->formatLoanPaymentData($payment)])
            : $this->send(['action' => 'delete', 'entity' => 'loan_payment', 'id' => $payment->reference_no ?: ('LRP-'.$payment->id)]);

        if ($payment->loan) {
            $this->syncLoan($payment->loan->fresh(['order.customer', 'recorder', 'payments']));
        }

        return $ok;
    }

    public function deleteLoan(Loan $loan): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        return $this->send([
            'action' => 'delete',
            'entity' => 'loan',
            'id' => $loan->reference_no ?: ('LOAN-'.$loan->id),
            'payment_ids' => $loan->payments->map(fn ($p) => $p->reference_no ?: ('LRP-'.$p->id))->values()->all(),
        ]);
    }

    public function deleteLoanPayment(LoanPayment $payment): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $ok = $this->send([
            'action' => 'delete',
            'entity' => 'loan_payment',
            'id' => $payment->reference_no ?: ('LRP-'.$payment->id),
        ]);

        if ($payment->loan) {
            $this->syncLoan($payment->loan->fresh(['order.customer', 'recorder', 'payments']));
        }

        return $ok;
    }

    /**
     * Add, update, or delete a single convection asset entry in Google Sheets.
     */
    public function syncAsset(ConvectionAsset $asset, string $action = 'add'): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        $asset->loadMissing('recorder');

        return $action === 'add'
            ? $this->send(['type' => 'asset', 'asset' => $this->formatAssetData($asset)])
            : $this->send(['action' => 'delete', 'entity' => 'asset', 'id' => $asset->code ?: ('AST-'.$asset->id)]);
    }

    public function deleteAsset(ConvectionAsset $asset): bool
    {
        if (! $this->isAutoSync()) {
            return false;
        }

        return $this->send([
            'action' => 'delete',
            'entity' => 'asset',
            'id' => $asset->code ?: ('AST-'.$asset->id),
        ]);
    }

    /* ---------------------------------------------------------------------- */
    /* Formatter Helpers (ATM Structure) */
    /* ---------------------------------------------------------------------- */

    public function formatOrderData(Order $order): array
    {
        $dp = $order->dp_paid;
        $settlement = $order->settlement_paid;
        $qty = (int) $order->total_quantity;
        $unitPrice = $qty > 0 ? (int) round($order->grand_total / $qty) : (int) $order->grand_total;

        // Status murni (Aktif / Selesai / Dibatalkan), label nombok kas dipisah ke kolom Dana Talangan
        $statusText = match ($order->status) {
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => 'Aktif ('.$order->current_stage_name.')',
        };

        return [
            'id' => 'ORD-'.$order->id,
            'date' => $order->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'order_number' => $order->order_number,
            'customer' => $order->customer?->name ?? '-',
            'product' => $order->name,
            'qty' => $qty,
            'price_per_unit' => $unitPrice,
            'total_order' => (int) $order->grand_total,
            'dp' => $dp,
            'settlement' => $settlement,
            'total_cost' => (int) $order->total_cost,
            'cash_advance' => (int) $order->cash_deficit, // Kolom Khusus: Dana Talangan / Nombok Kas
            'estimated_profit' => (int) $order->gross_profit,
            'remaining' => (int) $order->remaining,
            'status' => $statusText,
            'notes' => $order->notes ?? '-',
            'cash_position' => (int) $order->net_cash_flow,
            'cash_deficit' => (int) $order->cash_deficit,
            'cash_status' => $order->cash_status_meta['label'] ?? 'Aman',
            'is_completed' => $order->status === 'completed',
            'receivable_type' => $order->status === 'completed' ? 'Siap Ditagih' : ($order->status === 'active' ? 'Order Berjalan' : '-'),
        ];
    }

    public function formatCostData(OrderCost $cost): array
    {
        return [
            'id' => 'CST-'.$cost->id,
            'date' => $cost->spent_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'order_number' => $cost->order?->order_number ?? '-',
            'category' => $cost->category_label,
            'description' => $cost->description,
            'qty' => (float) $cost->quantity,
            'unit' => $cost->unit ?? 'pcs',
            'unit_price' => (int) $cost->unit_price,
            'amount' => (int) $cost->amount,
            'recorded_by' => $cost->recorder?->name ?? 'Admin Produksi',
        ];
    }

    public function formatPaymentData(Payment $payment): array
    {
        return [
            'id' => 'PAY-'.$payment->id,
            'date' => $payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'order_number' => $payment->order?->order_number ?? '-',
            'trx_no' => $payment->invoice?->invoice_number ?? $payment->order?->order_number,
            // Nomor pesanan sudah punya kolomnya sendiri di Arus Kas, jadi
            // keterangan diisi hal yang belum ada di kolom lain: catatan admin,
            // atau nama produk yang dibayar.
            'desc' => $payment->note ?: ($payment->order?->name ?: $payment->type_label),
            'category' => $payment->isDp() ? 'Penjualan/DP' : 'Pelunasan',
            'customer' => $payment->order?->customer?->name ?? '-',
            'amount' => (int) $payment->amount,
            'method' => $payment->method_label,
        ];
    }

    public function formatExpenseData(OperationalExpense $expense): array
    {
        return [
            'id' => $expense->reference_no ?: ('EXP-'.$expense->id),
            'date' => $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'trx_no' => $expense->reference_no,
            'desc' => '['.$expense->type_label.'] '.$expense->title.($expense->notes ? ' ('.$expense->notes.')' : ''),
            'title' => $expense->title,
            'category' => 'Beban Operasional — ['.$expense->type_label.'] '.$expense->category_label,
            'category_name' => $expense->category_label,
            'party' => $expense->category_label,
            'amount' => (int) $expense->amount,
            'method' => $expense->method_label,
            'recorded_by' => $expense->recorder?->name ?? 'Admin',
            'expense_type' => $expense->expense_type,
            'type_label' => $expense->type_label,
            'notes' => $expense->notes ?? '-',
        ];
    }

    public function formatLoanData(Loan $loan): array
    {
        return [
            'id' => $loan->reference_no ?: ('LOAN-'.$loan->id),
            'date' => $loan->loan_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'reference_no' => $loan->reference_no,
            'lender_name' => $loan->lender_name,
            'amount' => (int) $loan->amount,
            'amount_paid' => (int) $loan->amount_paid,
            'remaining_amount' => (int) $loan->remaining_amount,
            'due_date' => $loan->due_date?->format('Y-m-d') ?? '-',
            'status' => $loan->status_label,
            'order_number' => $loan->order?->order_number ?? '-',
            'purpose' => $loan->purpose ?: '-',
            'notes' => $loan->notes ?: '-',
            'recorded_by' => $loan->recorder?->name ?? 'Admin',
        ];
    }

    public function formatLoanPaymentData(LoanPayment $payment): array
    {
        return [
            'id' => $payment->reference_no ?: ('LRP-'.$payment->id),
            'date' => $payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'reference_no' => $payment->reference_no,
            'loan_reference_no' => $payment->loan?->reference_no ?? '-',
            'lender_name' => $payment->loan?->lender_name ?? '-',
            'amount' => (int) $payment->amount,
            'method' => $payment->method_label,
            'notes' => $payment->notes ?: '-',
            'recorded_by' => $payment->recorder?->name ?? 'Admin',
        ];
    }

    public function formatAssetData(ConvectionAsset $asset): array
    {
        return [
            'id' => $asset->code ?: ('AST-'.$asset->id),
            'code' => $asset->code,
            'date' => $asset->purchase_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'name' => $asset->name,
            'category' => $asset->category_label,
            'category_key' => $asset->category,
            'qty' => (int) $asset->quantity,
            'unit' => $asset->unit ?: 'unit',
            'purchase_price' => (int) $asset->purchase_price,
            'current_value' => (int) $asset->current_value,
            'condition' => $asset->condition_label,
            'condition_key' => $asset->condition,
            'location' => $asset->location ?: '-',
            'notes' => $asset->notes ?: '-',
            'recorded_by' => $asset->recorder?->name ?? 'Admin',
        ];
    }

    /* ---------------------------------------------------------------------- */
    /* HTTP Transport */
    /* ---------------------------------------------------------------------- */

    /**
     * Kirim sinkronisasi otomatis setelah respons dikirim ke browser.
     *
     * Sebelumnya panggilan HTTP ke Google berjalan di tengah request, sehingga
     * menyimpan pesanan ikut menunggu Google sampai 15 detik. Dijalankan di
     * callback terminating, halaman admin sudah tampil lebih dulu. Di CLI dan
     * saat pengujian tidak ada respons untuk ditunggu, jadi dijalankan langsung.
     */
    protected function send(array $payload): bool
    {
        if (app()->runningInConsole()) {
            return $this->sendPayload($payload)['success'];
        }

        app()->terminating(fn () => $this->sendPayload($payload));

        return true;
    }

    /**
     * Sinkronisasi yang gagal dicatat supaya panel bisa memberi tahu bahwa
     * spreadsheet sedang tertinggal — dulu kegagalan hilang tanpa jejak.
     */
    /**
     * Simpan keterangan yang dilaporkan skrip: versinya, dan alamat spreadsheet
     * tempat ia terpasang. URL webhook tidak memuat id spreadsheet, jadi ini
     * satu-satunya cara aplikasi tahu harus menautkan ke mana.
     */
    protected function rememberScriptInfo(mixed $body): void
    {
        if (! is_array($body)) {
            return;
        }

        foreach ([
            'version' => 'google_sheet_script_version',
            'sheet_url' => 'google_sheet_url',
            'sheet_name' => 'google_sheet_name',
        ] as $key => $setting) {
            if (! empty($body[$key])) {
                Setting::set($setting, (string) $body[$key]);
            }
        }
    }

    protected function rememberFailure(string $message): void
    {
        Setting::set('google_sheet_last_error', $message);
        Setting::set('google_sheet_last_error_at', now()->toIso8601String());
    }

    protected function forgetFailure(): void
    {
        if (Setting::get('google_sheet_last_error')) {
            Setting::set('google_sheet_last_error', '');
            Setting::set('google_sheet_last_error_at', '');
        }
    }

    /**
     * @return array{success: bool, data?: array, error?: string}
     */
    protected function sendPayload(array $payload, ?string $url = null, ?int $timeout = null): array
    {
        $targetUrl = $url ?? $this->getWebhookUrl();

        if (empty($targetUrl)) {
            return ['success' => false, 'error' => 'URL Webhook Google Sheets tidak tersedia.'];
        }

        $effectiveTimeout = $timeout ?? (in_array($payload['action'] ?? '', ['setup', 'sync_all']) ? self::BATCH_TIMEOUT : self::DEFAULT_TIMEOUT);

        try {
            $response = Http::timeout($effectiveTimeout)
                ->withOptions(['allow_redirects' => true])
                ->asJson()
                ->post($targetUrl, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $isSuccess = is_array($body) && (($body['status'] ?? '') === 'success' || ($body['success'] ?? false));

                if ($isSuccess || $response->status() === 200) {
                    $this->forgetFailure();
                    $this->rememberScriptInfo($body);

                    return [
                        'success' => true,
                        'data' => is_array($body) ? $body : ['raw' => $response->body()],
                    ];
                }

                $error = $body['message'] ?? $body['error'] ?? 'Response error dari Google Sheets.';
                $this->rememberFailure($error);
                Log::warning('Google Sheet sync rejected: '.$error);

                return ['success' => false, 'error' => $error];
            }

            // Periksa jika Google mengembalikan 404 (File tidak ditemukan / deployment belum aktif)
            if (str_contains($response->body(), 'Halaman Tidak Ditemukan') || str_contains($response->body(), 'tidak ada')) {
                $error = 'URL Webhook tidak ditemukan di Google (Error 404). Pastikan Web App sudah di-deploy dengan benar dan hak akses diatur ke "Anyone" (Siapa saja).';
                $this->rememberFailure($error);

                return ['success' => false, 'error' => $error];
            }

            $error = 'HTTP Error '.$response->status().': '.$response->reason();
            $this->rememberFailure($error);
            Log::warning('Google Sheet sync failed: '.$error);

            return ['success' => false, 'error' => $error];
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'timed out')) {
                $error = 'Waktu koneksi habis (Timeout setelah '.$effectiveTimeout.' detik). Google Webhook belum selesai memproses atau URL tidak merespons. Pastikan URL webhook valid dan hak akses diatur ke "Anyone".';
            } else {
                $error = 'Koneksi gagal: '.$msg;
            }

            Log::warning('Google Sheet Webhook Sync failed: '.$error);
            $this->rememberFailure($error);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSheetService
{
    public const DEFAULT_TIMEOUT = 15;

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
            return [
                'success' => false,
                'message' => 'Koneksi gagal: '.$e->getMessage(),
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

        $res = $this->sendPayload(['action' => 'setup'], $url);

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

        $formattedOrders = $orders->map(fn (Order $order) => $this->formatOrderData($order))->all();
        $formattedCosts = $costs->map(fn (OrderCost $cost) => $this->formatCostData($cost))->all();
        $formattedPayments = $payments->map(fn (Payment $payment) => $this->formatPaymentData($payment))->all();

        $payload = [
            'action' => 'sync_all',
            'orders' => $formattedOrders,
            'costs' => $formattedCosts,
            'payments' => $formattedPayments,
            'synced_at' => now()->translatedFormat('d F Y H:i:s'),
        ];

        $res = $this->sendPayload($payload, $url);

        if ($res['success']) {
            Setting::set('google_sheet_last_synced_at', now()->toIso8601String());

            return [
                'success' => true,
                'message' => 'Semua data berhasil disinkronkan ke Google Sheets.',
                'counts' => [
                    'orders' => $orders->count(),
                    'costs' => $costs->count(),
                    'payments' => $payments->count(),
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

    /* ---------------------------------------------------------------------- */
    /* Formatter Helpers (ATM Structure) */
    /* ---------------------------------------------------------------------- */

    public function formatOrderData(Order $order): array
    {
        $dp = $order->dp_paid;
        $settlement = $order->settlement_paid;
        $qty = (int) $order->total_quantity;
        $unitPrice = $qty > 0 ? (int) round($order->grand_total / $qty) : (int) $order->grand_total;

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
            'estimated_profit' => (int) $order->gross_profit,
            'remaining' => (int) $order->remaining,
            'status' => $order->status_label.' ('.$order->current_stage_name.')',
            'notes' => $order->notes ?? '-',
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
    protected function sendPayload(array $payload, ?string $url = null): array
    {
        $targetUrl = $url ?? $this->getWebhookUrl();

        if (empty($targetUrl)) {
            return ['success' => false, 'error' => 'URL Webhook Google Sheets tidak tersedia.'];
        }

        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT)
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

            $error = 'HTTP Error '.$response->status().': '.$response->reason();
            $this->rememberFailure($error);
            Log::warning('Google Sheet sync failed: '.$error);

            return ['success' => false, 'error' => $error];
        } catch (Throwable $e) {
            Log::warning('Google Sheet Webhook Sync failed: '.$e->getMessage());
            $this->rememberFailure('Koneksi gagal: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Koneksi gagal: '.$e->getMessage(),
            ];
        }
    }
}

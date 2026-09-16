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

            if ($response->successful()) {
                $body = $response->json();
                $isOk = (is_array($body) && (($body['status'] ?? '') === 'success' || ($body['success'] ?? false)));

                if ($isOk || $response->status() === 200) {
                    Setting::set('google_sheet_last_connected_at', now()->toIso8601String());

                    return [
                        'success' => true,
                        'message' => is_array($body) ? ($body['message'] ?? 'Koneksi ke Google Sheets berhasil!') : 'Koneksi ke Google Sheets berhasil!',
                    ];
                }
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

        $payload = [
            'type' => 'order',
            'order' => $this->formatOrderData($order),
        ];

        $res = $this->sendPayload($payload);

        return $res['success'];
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

        if ($action === 'add') {
            $payload = [
                'type' => 'cost',
                'cost' => $this->formatCostData($cost),
            ];
            $res = $this->sendPayload($payload);
        } else {
            $res = ['success' => true];
        }

        // Always refresh parent order row in sheet so Total HPP and Estimasi Laba update
        if ($cost->order) {
            $this->syncOrder($cost->order->fresh(['customer', 'items', 'costs', 'payments']));
        }

        return $res['success'];
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

        if ($action === 'add') {
            $payload = [
                'type' => 'payment',
                'payment' => $this->formatPaymentData($payment),
            ];
            $res = $this->sendPayload($payload);
        } else {
            $res = ['success' => true];
        }

        // Always refresh parent order row in sheet so DP, Pelunasan, and Sisa Tagihan update
        if ($payment->order) {
            $this->syncOrder($payment->order->fresh(['customer', 'items', 'costs', 'payments']));
        }

        return $res['success'];
    }

    /* ---------------------------------------------------------------------- */
    /* Formatter Helpers (ATM Structure) */
    /* ---------------------------------------------------------------------- */

    public function formatOrderData(Order $order): array
    {
        $dp = (int) $order->payments->filter(fn ($p) => stripos($p->note ?? '', 'DP') !== false)->sum('amount');
        $settlement = (int) $order->payments->filter(fn ($p) => stripos($p->note ?? '', 'DP') === false)->sum('amount');
        $qty = (int) $order->total_quantity;
        $unitPrice = $qty > 0 ? (int) round($order->grand_total / $qty) : (int) $order->grand_total;

        return [
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
        $isDp = stripos($payment->note ?? '', 'DP') !== false;

        return [
            'date' => $payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'order_number' => $payment->order?->order_number ?? '-',
            'trx_no' => $payment->invoice?->invoice_number ?? $payment->order?->order_number,
            'desc' => 'Pembayaran '.($payment->note ?: 'Pesanan '.($payment->order?->order_number ?? '')),
            'category' => $isDp ? 'Penjualan/DP' : 'Pelunasan',
            'customer' => $payment->order?->customer?->name ?? '-',
            'amount' => (int) $payment->amount,
            'method' => $payment->method_label,
        ];
    }

    /* ---------------------------------------------------------------------- */
    /* HTTP Transport */
    /* ---------------------------------------------------------------------- */

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
                    return [
                        'success' => true,
                        'data' => is_array($body) ? $body : ['raw' => $response->body()],
                    ];
                }

                return [
                    'success' => false,
                    'error' => $body['message'] ?? $body['error'] ?? 'Response error dari Google Sheets.',
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP Error '.$response->status().': '.$response->reason(),
            ];
        } catch (Throwable $e) {
            Log::warning('Google Sheet Webhook Sync failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Koneksi gagal: '.$e->getMessage(),
            ];
        }
    }
}

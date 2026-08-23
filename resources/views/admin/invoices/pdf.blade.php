<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #202020; padding: 36px 40px; }
        .header { width: 100%; border-bottom: 3px solid #6C1005; padding-bottom: 16px; }
        .header td { vertical-align: top; }
        .brand-name { font-size: 16px; font-weight: bold; color: #6C1005; }
        .muted { color: #666666; }
        .invoice-title { font-size: 22px; font-weight: bold; color: #6C1005; text-transform: uppercase; letter-spacing: 2px; text-align: right; }
        .invoice-number { font-size: 14px; font-weight: bold; text-align: right; margin-top: 4px; }
        .meta { width: 100%; margin-top: 20px; }
        .meta td { vertical-align: top; width: 33%; }
        .label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #666666; margin-bottom: 3px; }
        .value { font-size: 11px; font-weight: bold; }
        .items { width: 100%; margin-top: 24px; border-collapse: collapse; }
        .items th { background: #6C1005; color: #ffffff; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; padding: 8px 10px; text-align: left; }
        .items th.r, .items td.r { text-align: right; }
        .items td { padding: 8px 10px; border-bottom: 1px solid #E8E2DD; }
        .totals { width: 260px; margin-top: 12px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 4px 10px; }
        .totals .r { text-align: right; }
        .grand td { border-top: 2px solid #202020; font-size: 13px; font-weight: bold; padding-top: 8px; }
        .grand .amount { color: #6C1005; }
        .payment-box { margin-top: 24px; background: #F7F3EE; padding: 14px 16px; border-radius: 6px; }
        .payment-box table { width: 100%; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .status-paid { background: #d1fae5; color: #047857; }
        .status-partial { background: #fef3c7; color: #b45309; }
        .status-unpaid { background: #f5f5f5; color: #666666; }
        .footer-info { margin-top: 24px; width: 100%; }
        .footer-info td { vertical-align: top; width: 50%; padding-right: 16px; }
        .thanks { margin-top: 32px; text-align: center; color: #666666; font-size: 10px; border-top: 1px solid #E8E2DD; padding-top: 14px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand-name">{{ setting('invoice_company_name', 'Zada Karya Production') }}</div>
                <div class="muted" style="margin-top:4px;">{{ setting('invoice_address') }}</div>
                <div class="muted" style="margin-top:2px;">WhatsApp: {{ setting('whatsapp') }} &bull; Email: {{ setting('email') }}</div>
            </td>
            <td>
                <div class="invoice-title">Invoice</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="label">Ditagihkan Kepada</div>
                <div class="value">{{ $invoice->order->customer->name }}</div>
                @if($invoice->order->customer->company)<div>{{ $invoice->order->customer->company }}</div>@endif
                @if($invoice->order->customer->address)<div class="muted">{{ $invoice->order->customer->address }}{{ $invoice->order->customer->city ? ', '.$invoice->order->customer->city : '' }}</div>@endif
            </td>
            <td>
                <div class="label">Nomor Pesanan</div>
                <div class="value">{{ $invoice->order->order_number }}</div>
                <div class="muted">{{ $invoice->order->name }}</div>
            </td>
            <td>
                <div class="label">Tanggal Invoice</div>
                <div class="value">{{ $invoice->date->translatedFormat('d F Y') }}</div>
                @if($invoice->due_date)
                    <div class="label" style="margin-top:8px;">Jatuh Tempo</div>
                    <div class="value">{{ $invoice->due_date->translatedFormat('d F Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:46%;">Deskripsi</th>
                <th class="r" style="width:14%;">Qty</th>
                <th class="r" style="width:20%;">Harga Satuan</th>
                <th class="r" style="width:20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="r">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                    <td class="r">{{ rupiah($item->unit_price) }}</td>
                    <td class="r"><strong>{{ rupiah($item->total) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="r">{{ rupiah($invoice->subtotal) }}</td></tr>
        @if($invoice->discount > 0)
            <tr><td class="muted">Diskon</td><td class="r">- {{ rupiah($invoice->discount) }}</td></tr>
        @endif
        @if($invoice->additional_cost > 0)
            <tr><td class="muted">{{ $invoice->additional_cost_label ?: 'Biaya Tambahan' }}</td><td class="r">+ {{ rupiah($invoice->additional_cost) }}</td></tr>
        @endif
        <tr class="grand"><td>Grand Total</td><td class="r amount">{{ rupiah($invoice->grand_total) }}</td></tr>
    </table>

    @php
        $paid = $invoice->order->amount_paid;
        $remaining = $invoice->order->remaining;
        $status = $invoice->order->payment_status;
    @endphp
    <div class="payment-box">
        <div class="label">Ringkasan Pembayaran Pesanan {{ $invoice->order->order_number }}</div>
        <table style="margin-top:6px;">
            <tr>
                <td style="width:25%;"><div class="muted">Terbayar</div><strong>{{ rupiah($paid) }}</strong></td>
                <td style="width:25%;"><div class="muted">Sisa Pembayaran</div><strong>{{ rupiah($remaining) }}</strong></td>
                <td style="width:25%;">
                    <div class="muted">Status</div>
                    <span class="status status-{{ $status }}">{{ $invoice->order->payment_status_label }}</span>
                </td>
                <td style="width:25%;">
                    @if($invoice->order->dp_amount)
                        <div class="muted">DP</div><strong>{{ rupiah($invoice->order->dp_amount) }}</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="footer-info">
        <tr>
            @if(setting('invoice_bank_info'))
                <td>
                    <div class="label">Informasi Pembayaran</div>
                    <div style="white-space: pre-line;">{{ setting('invoice_bank_info') }}</div>
                </td>
            @endif
            @if($invoice->notes)
                <td>
                    <div class="label">Catatan</div>
                    <div style="white-space: pre-line;">{{ $invoice->notes }}</div>
                </td>
            @endif
        </tr>
    </table>

    <div class="thanks">
        Terima kasih atas kepercayaan Anda kepada {{ setting('invoice_company_name', 'Zada Karya Production') }}.<br>
        Dokumen ini dibuat secara otomatis oleh sistem dan sah tanpa tanda tangan.
    </div>
</body>
</html>

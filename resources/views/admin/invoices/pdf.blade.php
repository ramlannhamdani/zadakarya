@php
    $order = $invoice->order;
    $customer = $order->customer;

    $logoFile = setting('logo') ? storage_path('app/public/'.setting('logo')) : null;
    $logo = ($logoFile && file_exists($logoFile)) ? $logoFile : null;

    $instagram = setting('instagram');
    $igHandle = $instagram ? '@'.trim(basename(rtrim($instagram, '/')), '@') : null;

    // DP = nominal DP yang disepakati (atau pembayaran pertama); Pelunasan = sisa setelah DP.
    $dp = $order->dp_amount ?: ($order->payments->first()?->amount ?? 0);
    $settlement = max(0, $invoice->grand_total - $dp);
    $isPaid = $order->payment_status === 'paid';

    $terms = collect(preg_split('/\r?\n/', (string) setting('invoice_terms', "Barang yang sudah dipesan tidak bisa dibatalkan\nPelunasan wajib dilakukan sebelum pengambilan barang\nTerima kasih telah mempercayakan konveksi kepada kami")))
        ->map(fn ($t) => trim($t))->filter()->values();

    $bankLines = collect(preg_split('/\r?\n/', (string) setting('invoice_bank_info')))->map(fn ($t) => trim($t))->filter()->values();

    $rows = $invoice->items;
    $minRows = 6;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 13px; color: #6C1005; line-height: 1.3; margin: 0; }
        /* dompdf mengabaikan @page margin di versi ini -> margin halaman dibuat dari padding wrapper. */
        .sheet { padding: 24px 42px; }
        table { border-collapse: collapse; }
        td { vertical-align: top; }

        .header { width: 100%; }
        .logo-cell { width: 1%; white-space: nowrap; padding-right: 18px; }
        .brand { font-size: 19px; font-weight: bold; letter-spacing: 0.4px; }
        .contact { font-size: 12.5px; line-height: 1.45; margin-top: 2px; }
        .contact td { padding: 0 5px 0 0; }
        .title-box { border: 2px solid #6C1005; padding: 8px 26px; font-size: 26px; font-weight: bold; letter-spacing: 1px; text-align: center; }
        .meta { font-size: 12px; text-align: right; margin-top: 4px; }
        .rule { border-top: 3px solid #6C1005; margin: 8px 0 10px; }

        .section-title { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
        .kv td { padding: 2px 0; font-size: 13px; }
        .kv .k { width: 128px; }
        .kv .c { width: 10px; }
        .dots { border-bottom: 1px dotted #6C1005; display: inline-block; min-width: 120px; }

        .items { width: 100%; margin-top: 12px; border: 2px solid #6C1005; }
        .items th { border: 1px solid #6C1005; border-bottom: 2px solid #6C1005; padding: 6px 9px; font-size: 13.5px; font-weight: bold; text-align: center; }
        .items td { border: 1px solid #6C1005; padding: 6px 9px; height: 30px; font-size: 13px; }
        .items .no { width: 36px; text-align: center; }
        .items .qty { width: 120px; text-align: center; }
        .items .price, .items .total { width: 140px; text-align: right; }

        .bottom { width: 100%; margin-top: 10px; }
        .bottom td { vertical-align: top; }
        .note-title { font-size: 15px; font-weight: bold; }
        .note-text { font-size: 12.5px; margin-top: 2px; white-space: pre-line; }
        .note-line { border-bottom: 1px dotted #6C1005; height: 15px; }
        .terms { margin-top: 8px; font-size: 12px; font-weight: bold; }
        .terms p { margin-bottom: 2px; letter-spacing: 0.2px; }
        .terms li { margin-left: 14px; margin-bottom: 2px; text-transform: uppercase; }

        .totals { width: 100%; }
        .totals td { padding: 1px 0; text-align: right; }
        .totals .lbl { font-size: 13px; }
        .totals .amt { font-size: 13px; width: 160px; padding-left: 18px; }
        .totals .total-label { font-size: 16px; font-weight: bold; padding-top: 4px; }
        .totals .total-value { font-size: 16px; font-weight: bold; width: 160px; padding-left: 18px; padding-top: 4px; }

        .sign { width: 100%; margin-top: 14px; }
        .sign td { text-align: center; font-size: 13px; padding: 0 12px; }
        .sign .line { border-top: 2px solid #6C1005; height: 0; margin-top: 40px; }
        .box { display: inline-block; width: 12px; height: 12px; border: 1px solid #6C1005; vertical-align: middle; margin-left: 4px; }
        .box.on { background: #6C1005; }
    </style>
</head>
<body>
<div class="sheet">

    {{-- Header --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logo)
                    <img src="{{ $logo }}" style="height: 62px; width: auto; max-width: 260px;">
                @else
                    <div style="width: 58px; height: 58px; background: #6C1005; color: #fff; font-weight: bold; font-size: 22px; text-align: center; line-height: 58px;">ZK</div>
                @endif
            </td>
            <td>
                @unless($logo)
                    <div class="brand">{{ strtoupper(setting('invoice_company_name', setting('company_name', 'Zada Karya Production'))) }}</div>
                @endunless
                <table class="contact">
                    @if($igHandle)<tr><td>IG</td><td>:</td><td>{{ $igHandle }}</td></tr>@endif
                    <tr><td>WA</td><td>:</td><td>{{ setting('whatsapp') }}</td></tr>
                    <tr><td>EMAIL</td><td>:</td><td>{{ setting('email') }}</td></tr>
                </table>
            </td>
            <td style="width: 190px; text-align: right;">
                <div class="title-box">INVOICE</div>
                <div class="meta">No. {{ $invoice->invoice_number }} &nbsp;|&nbsp; {{ $invoice->date->translatedFormat('d F Y') }}</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- Invoice to / rekening / payment details --}}
    <table style="width: 100%;">
        <tr>
            <td style="width: 36%; padding-right: 10px;">
                <div class="section-title">INVOICE TO :</div>
                <table class="kv">
                    <tr><td class="k">Nama</td><td class="c">:</td><td>{{ $customer->name }}</td></tr>
                    <tr><td class="k">Alamat/ Instansi</td><td class="c">:</td><td>{{ $customer->company ?: '' }}{{ $customer->company && $customer->address ? ', ' : '' }}{{ $customer->address ?: ($customer->company ? '' : '-') }}</td></tr>
                    <tr><td class="k">No Telepon/ WA</td><td class="c">:</td><td>{{ $customer->whatsapp ?: '-' }}</td></tr>
                    <tr><td class="k">Tanggal Pemesanan</td><td class="c">:</td><td>{{ $order->created_at->translatedFormat('d F Y') }}</td></tr>
                    <tr><td class="k">No. Pesanan</td><td class="c">:</td><td>{{ $order->order_number }}</td></tr>
                </table>
            </td>
            <td style="width: 32%; padding-top: 22px; padding-right: 10px;">
                <table class="kv">
                    @if($bankLines->isNotEmpty())
                        @foreach($bankLines as $i => $line)
                            @php [$label, $val] = array_pad(explode(':', $line, 2), 2, null); @endphp
                            @if($val !== null)
                                <tr><td class="k">{{ trim($label) }}</td><td class="c">:</td><td>{{ trim($val) }}</td></tr>
                            @else
                                <tr><td colspan="3">{{ $line }}</td></tr>
                            @endif
                        @endforeach
                    @else
                        <tr><td class="k">Bank</td><td class="c">:</td><td><span class="dots"></span></td></tr>
                        <tr><td class="k">Account Name</td><td class="c">:</td><td><span class="dots"></span></td></tr>
                        <tr><td class="k">Account No.</td><td class="c">:</td><td><span class="dots"></span></td></tr>
                    @endif
                </table>
            </td>
            <td style="width: 32%;">
                <div class="section-title">PAYMENT DETAILS :</div>
                <table class="kv">
                    <tr><td class="k">Uang Muka (DP)</td><td class="c">:</td><td>{{ $dp > 0 ? rupiah($dp) : '-' }}</td></tr>
                    <tr><td class="k">Pelunasan</td><td class="c">:</td><td>{{ rupiah($settlement) }}</td></tr>
                    <tr><td class="k">Terbayar</td><td class="c">:</td><td>{{ rupiah($order->amount_paid) }} &nbsp;(sisa {{ rupiah($order->remaining) }})</td></tr>
                    <tr>
                        <td class="k">Status</td><td class="c">:</td>
                        <td>Lunas <span class="box{{ $isPaid ? ' on' : '' }}"></span> &nbsp;&nbsp; Belum Lunas <span class="box{{ $isPaid ? '' : ' on' }}"></span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <th class="no">NO</th>
                <th>Nama Barang / Jenis Produk</th>
                <th class="qty">Jumlah</th>
                <th class="price">Harga</th>
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $item)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="qty">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                    <td class="price">{{ rupiah($item->unit_price) }}</td>
                    <td class="total">{{ rupiah($item->total) }}</td>
                </tr>
            @endforeach
            @for($i = $rows->count(); $i < $minRows; $i++)
                <tr><td class="no">&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    {{-- Bottom: keterangan + catatan | total + tanda tangan --}}
    <table class="bottom">
        <tr>
            <td style="width: 55%; padding-right: 24px;">
                <div class="note-title">Keterangan Tambahan</div>
                @if($invoice->notes)
                    <div class="note-text">{{ $invoice->notes }}</div>
                @else
                    <div class="note-line" style="width: 70%;"></div>
                    <div class="note-line" style="width: 70%;"></div>
                @endif

                <div class="terms">
                    <p>CATATAN :</p>
                    <ul>
                        @foreach($terms as $term)
                            <li>{{ $term }}</li>
                        @endforeach
                    </ul>
                </div>
            </td>
            <td style="width: 45%;">
                <table class="totals">
                    @if($invoice->discount > 0 || $invoice->additional_cost > 0)
                        <tr><td class="lbl">Subtotal :</td><td class="amt">{{ rupiah($invoice->subtotal) }}</td></tr>
                        @if($invoice->discount > 0)<tr><td class="lbl">Diskon :</td><td class="amt">- {{ rupiah($invoice->discount) }}</td></tr>@endif
                        @if($invoice->additional_cost > 0)<tr><td class="lbl">{{ $invoice->additional_cost_label ?: 'Biaya Tambahan' }} :</td><td class="amt">+ {{ rupiah($invoice->additional_cost) }}</td></tr>@endif
                    @endif
                    <tr><td class="total-label">TOTAL :</td><td class="total-value">{{ rupiah($invoice->grand_total) }}</td></tr>
                </table>
                <table class="sign">
                    <tr>
                        <td style="width: 50%;">Customer<div class="line"></div></td>
                        <td style="width: 50%;">Hormat kami<div class="line"></div></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>

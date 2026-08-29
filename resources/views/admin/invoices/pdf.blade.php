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
        @page { margin: 40px 48px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; color: #6C1005; line-height: 1.35; }
        table { border-collapse: collapse; }
        td { vertical-align: top; }

        .header { width: 100%; }
        .logo-cell { width: 1%; white-space: nowrap; padding-right: 14px; }
        .brand { font-size: 17px; font-weight: bold; letter-spacing: 0.4px; }
        .contact { font-size: 11px; line-height: 1.5; margin-top: 3px; }
        .contact td { padding: 0 5px 0 0; }
        .title-box { border: 2px solid #6C1005; padding: 8px 22px; font-size: 22px; font-weight: bold; letter-spacing: 1px; text-align: center; }
        .meta { font-size: 10.5px; text-align: right; margin-top: 5px; }
        .rule { border-top: 3px solid #6C1005; margin: 10px 0 12px; }

        .section-title { font-size: 13px; font-weight: bold; margin-bottom: 6px; }
        .kv td { padding: 2px 0; font-size: 11.5px; }
        .kv .k { width: 118px; }
        .kv .c { width: 10px; }
        .dots { border-bottom: 1px dotted #6C1005; display: inline-block; min-width: 120px; }

        .items { width: 100%; margin-top: 14px; border: 2px solid #6C1005; }
        .items th { border: 1px solid #6C1005; border-bottom: 2px solid #6C1005; padding: 6px 8px; font-size: 12px; font-weight: bold; text-align: center; }
        .items td { border: 1px solid #6C1005; padding: 6px 8px; height: 30px; font-size: 11.5px; }
        .items .no { width: 34px; text-align: center; }
        .items .qty { width: 120px; text-align: center; }
        .items .price, .items .total { width: 130px; text-align: right; }

        .bottom { width: 100%; margin-top: 12px; }
        .bottom td { vertical-align: top; }
        .note-title { font-size: 13px; font-weight: bold; }
        .note-line { border-bottom: 1px dotted #6C1005; height: 16px; }
        .terms { margin-top: 12px; font-size: 10.5px; font-weight: bold; }
        .terms p { margin-bottom: 2px; letter-spacing: 0.2px; }
        .terms li { margin-left: 14px; margin-bottom: 2px; text-transform: uppercase; }
        .total-label { font-size: 13px; font-weight: bold; }
        .total-value { font-size: 13px; font-weight: bold; text-align: right; }
        .sub td { font-size: 11.5px; padding: 1px 0; }
        .sign { width: 100%; margin-top: 22px; }
        .sign td { text-align: center; font-size: 11.5px; padding: 0 12px; }
        .sign .line { border-top: 2px solid #6C1005; height: 0; margin-top: 44px; }
        .box { display: inline-block; width: 11px; height: 11px; border: 1px solid #6C1005; vertical-align: middle; margin-left: 4px; }
        .box.on { background: #6C1005; }
    </style>
</head>
<body>

    {{-- Header --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logo)
                    <img src="{{ $logo }}" style="height: 56px; width: auto; max-width: 240px;">
                @else
                    <div style="width: 58px; height: 58px; background: #6C1005; color: #fff; font-weight: bold; font-size: 22px; text-align: center; line-height: 58px;">ZK</div>
                @endif
            </td>
            <td>
                <div class="brand">{{ strtoupper(setting('invoice_company_name', setting('company_name', 'Zada Karya Production'))) }}</div>
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
            <td style="width: 34%;">
                <div class="section-title">INVOICE TO :</div>
                <table class="kv">
                    <tr><td class="k">Nama</td><td class="c">:</td><td>{{ $customer->name }}</td></tr>
                    <tr><td class="k">Alamat/ Instansi</td><td class="c">:</td><td>{{ $customer->company ?: '' }}{{ $customer->company && $customer->address ? ', ' : '' }}{{ $customer->address ?: ($customer->company ? '' : '-') }}</td></tr>
                    <tr><td class="k">No Telepon/ WA</td><td class="c">:</td><td>{{ $customer->whatsapp ?: '-' }}</td></tr>
                    <tr><td class="k">Tanggal Pemesanan</td><td class="c">:</td><td>{{ $order->created_at->translatedFormat('d F Y') }}</td></tr>
                    <tr><td class="k">No. Pesanan</td><td class="c">:</td><td>{{ $order->order_number }}</td></tr>
                </table>
            </td>
            <td style="width: 33%; padding-top: 22px;">
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
            <td style="width: 33%;">
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
                    <div style="font-size: 11px; margin-top: 3px; white-space: pre-line;">{{ $invoice->notes }}</div>
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
                <table style="width: 100%;">
                    @if($invoice->discount > 0 || $invoice->additional_cost > 0)
                        <tr class="sub"><td>Subtotal</td><td style="text-align: right;">{{ rupiah($invoice->subtotal) }}</td></tr>
                        @if($invoice->discount > 0)<tr class="sub"><td>Diskon</td><td style="text-align: right;">- {{ rupiah($invoice->discount) }}</td></tr>@endif
                        @if($invoice->additional_cost > 0)<tr class="sub"><td>{{ $invoice->additional_cost_label ?: 'Biaya Tambahan' }}</td><td style="text-align: right;">+ {{ rupiah($invoice->additional_cost) }}</td></tr>@endif
                    @endif
                    <tr>
                        <td class="total-label" style="padding-top: 4px;">TOTAL :</td>
                        <td class="total-value" style="padding-top: 4px;">{{ rupiah($invoice->grand_total) }}</td>
                    </tr>
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

</body>
</html>

@php
    $order = $invoice->order;
    $customer = $order->customer;

    // Header memakai emblem saja (tanpa teks) = gambar favicon. Harus raster (png/jpg/webp); dompdf tidak bisa membaca .ico.
    $markFile = setting('favicon') ? storage_path('app/public/'.setting('favicon')) : null;
    $logo = ($markFile && file_exists($markFile) && in_array(strtolower(pathinfo($markFile, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp'], true)) ? $markFile : null;

    $signFile = setting('invoice_signature') ? storage_path('app/public/'.setting('invoice_signature')) : null;
    $signature = ($signFile && file_exists($signFile)) ? $signFile : null;

    // Ukuran tanda tangan mengikuti rasio gambarnya: tinggi dipatok, tapi
    // dikecilkan otomatis kalau hasilnya lebih lebar dari kolom tanda tangan.
    $signHeight = 100;
    $signWidth = $signHeight;

    if ($signature && ($dim = @getimagesize($signature)) && $dim[0] > 0 && $dim[1] > 0) {
        $ratio = $dim[0] / $dim[1];
        $signWidth = $signHeight * $ratio;

        if ($signWidth > 215) {
            $signWidth = 215;
            $signHeight = (int) round(215 / $ratio);
        }
    }

    // Stempel LUNAS: hanya dicetak kalau pesanan sudah lunas.
    $stampFile = setting('invoice_stamp') ? storage_path('app/public/'.setting('invoice_stamp')) : null;
    $stamp = ($stampFile && file_exists($stampFile)) ? $stampFile : null;
    $signer = setting('invoice_signer') ?: setting('invoice_company_name', setting('company_name', 'Zada Karya Production'));

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
        body { font-family: "Times New Roman", Times, serif; font-size: 14.5px; color: #6C1005; line-height: 1.28; margin: 0; }
        /* dompdf mengabaikan @page margin di versi ini -> margin halaman dibuat dari padding wrapper. */
        .sheet { padding: 20px 40px; }
        table { border-collapse: collapse; }
        td { vertical-align: top; }

        .header { width: 100%; }
        .logo-cell { width: 1%; white-space: nowrap; padding-right: 18px; }
        .brand { font-size: 20px; font-weight: bold; letter-spacing: 0.4px; }
        .contact { font-size: 14px; line-height: 1.4; margin-top: 2px; }
        .contact td { padding: 0 6px 0 0; }
        .title-box { border: 2px solid #6C1005; padding: 8px 28px; font-size: 28px; font-weight: bold; letter-spacing: 1px; text-align: center; }
        .meta { font-size: 13px; text-align: right; margin-top: 4px; }
        .rule { border-top: 3px solid #6C1005; margin: 8px 0 8px; }

        .section-title { font-size: 16.5px; font-weight: bold; margin-bottom: 3px; }
        .kv td { padding: 1.5px 0; font-size: 14.5px; }
        .kv .k { width: 126px; }
        .kv .c { width: 11px; }
        .dots { border-bottom: 1px dotted #6C1005; display: inline-block; min-width: 120px; }

        .items { width: 100%; margin-top: 10px; border: 2px solid #6C1005; }
        .items th { border: 1px solid #6C1005; border-bottom: 2px solid #6C1005; padding: 6px 9px; font-size: 15px; font-weight: bold; text-align: center; }
        .items td { border: 1px solid #6C1005; padding: 5px 9px; height: 29px; font-size: 14.5px; }
        .items .no { width: 40px; text-align: center; }
        .items .qty { width: 125px; text-align: center; }
        .items .price, .items .total { width: 150px; text-align: right; }

        .bottom { width: 100%; margin-top: 8px; }
        .bottom td { vertical-align: top; }
        .note-title { font-size: 16.5px; font-weight: bold; }
        .note-text { font-size: 14px; margin-top: 2px; white-space: pre-line; }
        .note-line { border-bottom: 1px dotted #6C1005; height: 15px; }
        .terms { margin-top: 6px; font-size: 13px; font-weight: bold; }
        .terms p { margin-bottom: 1px; letter-spacing: 0.2px; }
        .terms li { margin-left: 14px; margin-bottom: 1px; text-transform: uppercase; }

        .totals { width: 100%; }
        .totals td { padding: 1px 0; text-align: right; }
        .totals .lbl { font-size: 14.5px; }
        .totals .amt { font-size: 14.5px; width: 170px; padding-left: 18px; }
        .totals .total-label { font-size: 18px; font-weight: bold; padding-top: 2px; }
        .totals .total-value { font-size: 18px; font-weight: bold; width: 170px; padding-left: 18px; padding-top: 2px; }

        .sign { width: 100%; margin-top: 26px; }
        .sign td { text-align: center; font-size: 14.5px; padding: 0 10px; width: 50%; }
        .sign .space { height: 64px; vertical-align: bottom; }
        /* Tanda tangan & stempel: elemen absolut di level halaman (dompdf hanya
           menghitung position:absolute dengan benar untuk anak langsung <body>),
           sengaja menimpa garis tanda tangan agar terlihat seperti tanda basah. */
        .signature { position: absolute; width: auto; }
        .sign .line { border-top: 2px solid #6C1005; height: 0; margin-bottom: 3px; }
        .sign .name { font-size: 13.5px; }
        /* Stempel LUNAS: elemen absolut di level halaman (dompdf hanya menghitung
           position:absolute dengan benar untuk anak langsung <body>), diletakkan
           di antara kolom Customer dan Hormat kami seperti stempel basah. */
        .stamp { position: absolute; width: 146px; height: auto; }

        /* Watermark status pembayaran: anak pertama <body> + z-index -1 -> dirender paling awal (di belakang konten). */
        .watermark { position: absolute; z-index: -1; left: 0; width: 100%; text-align: center; font-weight: bold; color: #6C1005; opacity: 0.08; letter-spacing: 6px; transform: rotate(-18deg); }
        .watermark.paid { top: 290px; font-size: 170px; }
        .watermark.unpaid { top: 310px; font-size: 118px; }
    </style>
</head>
<body>
{{-- Kalau stempel LUNAS terpasang, watermark tidak dicetak supaya tidak dobel. --}}
@unless($isPaid && $stamp)
    <div class="watermark {{ $isPaid ? 'paid' : 'unpaid' }}">{{ $isPaid ? 'LUNAS' : 'BELUM LUNAS' }}</div>
@endunless

@if($isPaid && $stamp)
    {{-- Blok tanda tangan turun ~29px untuk tiap baris item di atas enam baris. --}}
    @php $stampTop = 562 + max(0, $rows->count() - $minRows) * 29; @endphp
    <img src="{{ $stamp }}" class="stamp" style="left: 778px; top: {{ $stampTop }}px;" alt="">
@endif

@if($signature)
    {{-- Dipusatkan di kolom "Hormat kami"; bagian bawahnya sengaja melewati
         garis tanda tangan ~14px supaya terlihat seperti tanda tangan basah. --}}
    @php
        $extraRows = max(0, $rows->count() - $minRows);
        // 668 = titik acuan hasil pengukuran render dompdf (bukan koordinat garis di CSS).
        $signatureTop = 688 - $signHeight + $extraRows * 29;
        $signatureLeft = (int) round(972 - $signWidth / 2);
    @endphp
    <img src="{{ $signature }}" class="signature"
         style="left: {{ $signatureLeft }}px; top: {{ $signatureTop }}px; height: {{ $signHeight }}px;" alt="">
@endif
<div class="sheet">

    {{-- Header --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logo)
                    <img src="{{ $logo }}" style="height: 74px; width: auto;">
                @else
                    <div style="width: 74px; height: 74px; background: #6C1005; color: #fff; font-weight: bold; font-size: 26px; text-align: center; line-height: 74px;">ZK</div>
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
            <td style="width: 210px; text-align: right;">
                <div class="title-box">INVOICE</div>
                <div class="meta">
                    No. {{ $invoice->invoice_number }} &nbsp;|&nbsp; {{ $invoice->date->translatedFormat('d F Y') }}
                    @if($invoice->invoice_number !== $order->order_number)<br>Pesanan {{ $order->order_number }}@endif
                </div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- Invoice to / rekening / payment details --}}
    <table style="width: 100%;">
        <tr>
            <td style="width: 37%; padding-right: 12px;">
                <div class="section-title">INVOICE TO :</div>
                <table class="kv">
                    <tr><td class="k">Nama</td><td class="c">:</td><td>{{ $customer->name }}</td></tr>
                    <tr><td class="k">Alamat/ Instansi</td><td class="c">:</td><td>{{ $customer->company ?: '' }}{{ $customer->company && $customer->address ? ', ' : '' }}{{ $customer->address ?: ($customer->company ? '' : '-') }}</td></tr>
                    <tr><td class="k">No Telepon/ WA</td><td class="c">:</td><td>{{ $customer->whatsapp ?: '-' }}</td></tr>
                    <tr><td class="k">Tanggal Pemesanan</td><td class="c">:</td><td>{{ $order->created_at->translatedFormat('d F Y') }}</td></tr>
                </table>
            </td>
            <td style="width: 31%; padding-top: 22px; padding-right: 12px;">
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
                    <tr><td class="k">Terbayar</td><td class="c">:</td><td>{{ rupiah($order->amount_paid) }}</td></tr>
                    <tr><td class="k">Sisa Tagihan</td><td class="c">:</td><td>{{ rupiah($order->remaining) }}</td></tr>
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
                <div>
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
                        <td>Customer,</td>
                        <td>Hormat kami,</td>
                    </tr>
                    <tr>
                        <td class="space"></td>
                        <td class="space"></td>
                    </tr>
                    <tr>
                        <td><div class="line"></div><span class="name">{{ $customer->name }}</span></td>
                        <td><div class="line"></div><span class="name">{{ $signer }}</span></td>
                    </tr>
                </table>
                </div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>

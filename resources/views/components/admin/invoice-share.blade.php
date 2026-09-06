@props(['invoice', 'label' => 'Kirim ke WA', 'small' => false])

@php
    $order = $invoice->order;

    // Angka mengikuti invoice ini saja, sama seperti yang tercetak di PDF-nya.
    $paid = $order->invoices->count() > 1
        ? (int) $order->payments->where('invoice_id', $invoice->id)->sum('amount')
        : (int) $order->amount_paid;
    $outstanding = max(0, $invoice->grand_total - $paid);

    $company = setting('invoice_company_name') ?: setting('company_name', 'Zada Karya Production');
    $link = $invoice->publicUrl();

    $message = implode("\n", array_filter([
        'Halo '.($order->customer?->name ?: 'Kak').', berikut invoice '.$invoice->invoice_number.' dari '.$company.'.',
        '',
        'Total: '.rupiah($invoice->grand_total),
        $outstanding > 0 ? 'Sisa tagihan: '.rupiah($outstanding) : 'Status: LUNAS — terima kasih!',
        '',
        $link,
    ], fn ($line) => $line !== null));

    $filename = 'Invoice '.$invoice->invoice_number.'.pdf';
@endphp

<span x-data="invoiceShare({
        pdfUrl: @js(route('admin.invoices.pdf', [$invoice, 'inline' => 1])),
        waUrl: @js(wa_send_link($order->customer?->whatsapp, $message)),
        filename: @js($filename),
        title: @js('Invoice '.$invoice->invoice_number),
        text: @js($message),
     })" class="contents">
    <button type="button" x-on:click="share()" :disabled="busy"
            title="{{ wa_number($order->customer?->whatsapp) ? 'Kirim ke '.$order->customer->whatsapp : 'Nomor WhatsApp customer belum tersimpan — kontak dipilih di WhatsApp' }}"
            @class([
                'inline-flex items-center gap-1.5 rounded-lg border border-green-600/30 font-semibold text-green-700 hover:bg-green-50 disabled:opacity-60',
                'px-2.5 py-1 text-xs' => $small,
                'px-4 py-2 text-xs' => ! $small,
            ])>
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.48-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.06 2.87 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.69.25-1.28.17-1.41-.07-.13-.27-.2-.57-.35Z"/>
            <path d="M12.04 2C6.6 2 2.17 6.43 2.16 11.88c0 1.74.46 3.44 1.32 4.94L2 22l5.32-1.4a9.86 9.86 0 0 0 4.71 1.2h.01c5.44 0 9.87-4.43 9.88-9.88a9.8 9.8 0 0 0-2.89-6.99A9.8 9.8 0 0 0 12.04 2Zm0 18.02h-.01a8.2 8.2 0 0 1-4.18-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.16 8.16 0 0 1-1.25-4.36c0-4.53 3.69-8.21 8.22-8.21a8.16 8.16 0 0 1 5.8 2.41 8.15 8.15 0 0 1 2.4 5.81c0 4.53-3.69 8.21-8.2 8.21Z"/>
        </svg>
        {{-- Label ditulis statis supaya tombolnya tetap terbaca sebelum Alpine jalan. --}}
        <span x-show="! busy">{{ $label }}</span>
        <span x-show="busy" x-cloak>Menyiapkan&hellip;</span>
    </button>
</span>

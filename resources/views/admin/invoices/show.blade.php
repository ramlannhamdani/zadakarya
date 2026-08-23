@extends('layouts.admin')

@section('title', 'Invoice '.$invoice->invoice_number)

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.invoices.index') }}" class="text-sm font-medium text-neutral-500 hover:text-brand-600">&larr; Semua Invoice</a>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn-outline !px-4 !py-2 text-xs">Edit</a>
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn-primary !px-4 !py-2 text-xs">Unduh PDF</a>
        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus invoice {{ $invoice->invoice_number }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button>
        </form>
    </div>
</div>

{{-- Invoice preview --}}
<div class="admin-card mx-auto max-w-3xl !p-8 sm:!p-10">
    <div class="flex items-start justify-between border-b-4 border-brand-600 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-sm font-extrabold text-white">ZK</span>
                <div>
                    <p class="font-extrabold text-ink">{{ setting('invoice_company_name', 'Zada Karya Production') }}</p>
                    <p class="text-xs text-neutral-500">{{ setting('invoice_address') }}</p>
                </div>
            </div>
            <p class="mt-2 text-xs text-neutral-500">WhatsApp: {{ setting('whatsapp') }} &bull; Email: {{ setting('email') }}</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-extrabold uppercase tracking-wide text-brand-600">Invoice</p>
            <p class="mt-1 font-mono text-lg font-bold">{{ $invoice->invoice_number }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ditagihkan Kepada</p>
            <p class="mt-1 font-bold text-ink">{{ $invoice->order->customer->name }}</p>
            @if($invoice->order->customer->company)<p class="text-sm">{{ $invoice->order->customer->company }}</p>@endif
            @if($invoice->order->customer->address)<p class="text-sm text-neutral-500">{{ $invoice->order->customer->address }}</p>@endif
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Nomor Pesanan</p>
            <p class="mt-1 font-mono font-bold">{{ $invoice->order->order_number }}</p>
            <p class="text-sm text-neutral-500">{{ $invoice->order->name }}</p>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Tanggal</p>
            <p class="mt-1 font-semibold">{{ $invoice->date->translatedFormat('d F Y') }}</p>
            @if($invoice->due_date)
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-500 mt-2">Jatuh Tempo</p>
                <p class="font-semibold">{{ $invoice->due_date->translatedFormat('d F Y') }}</p>
            @endif
        </div>
    </div>

    <table class="mt-8 w-full text-sm">
        <thead>
            <tr class="bg-cream text-left text-xs font-bold uppercase tracking-wider text-neutral-600">
                <th class="rounded-l-lg px-4 py-3">Deskripsi</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3 text-right">Harga Satuan</th>
                <th class="rounded-r-lg px-4 py-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @foreach($invoice->items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $item->description }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                    <td class="px-4 py-3 text-right">{{ rupiah($item->unit_price) }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ rupiah($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 ml-auto max-w-xs space-y-1.5 text-sm">
        <div class="flex justify-between"><span class="text-neutral-500">Subtotal</span><span class="font-semibold">{{ rupiah($invoice->subtotal) }}</span></div>
        @if($invoice->discount > 0)
            <div class="flex justify-between"><span class="text-neutral-500">Diskon</span><span class="font-semibold text-red-500">- {{ rupiah($invoice->discount) }}</span></div>
        @endif
        @if($invoice->additional_cost > 0)
            <div class="flex justify-between"><span class="text-neutral-500">{{ $invoice->additional_cost_label ?: 'Biaya Tambahan' }}</span><span class="font-semibold">+ {{ rupiah($invoice->additional_cost) }}</span></div>
        @endif
        <div class="flex justify-between border-t-2 border-line pt-2 text-base"><span class="font-bold">Grand Total</span><span class="font-extrabold text-brand-600">{{ rupiah($invoice->grand_total) }}</span></div>
    </div>

    <div class="mt-6 rounded-lg bg-cream p-4">
        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Ringkasan Pembayaran Pesanan</p>
        <div class="mt-2 grid grid-cols-3 gap-4 text-sm">
            <div><p class="text-neutral-500">Terbayar</p><p class="font-bold text-green-600">{{ rupiah($invoice->order->amount_paid) }}</p></div>
            <div><p class="text-neutral-500">Sisa</p><p class="font-bold text-brand-600">{{ rupiah($invoice->order->remaining) }}</p></div>
            <div><p class="text-neutral-500">Status</p><x-payment-badge :status="$invoice->order->payment_status" class="mt-0.5" /></div>
        </div>
    </div>

    @if($invoice->notes || setting('invoice_bank_info'))
        <div class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            @if(setting('invoice_bank_info'))
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Pembayaran</p>
                    <p class="mt-1 whitespace-pre-line text-neutral-600">{{ setting('invoice_bank_info') }}</p>
                </div>
            @endif
            @if($invoice->notes)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Catatan</p>
                    <p class="mt-1 whitespace-pre-line text-neutral-600">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

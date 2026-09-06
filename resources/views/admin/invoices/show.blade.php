@extends('layouts.admin')

@section('title', 'Invoice '.$invoice->invoice_number)

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.invoices.index') }}" class="text-sm font-medium text-neutral-500 hover:text-brand-600">&larr; Semua Invoice</a>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.orders.show', ['order' => $invoice->order, 'tab' => 'invoice']) }}" class="btn-outline !px-4 !py-2 text-xs">Buka Pesanan</a>
        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn-outline !px-4 !py-2 text-xs">Edit</a>
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn-primary !px-4 !py-2 text-xs">Unduh PDF</a>
        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus invoice {{ $invoice->invoice_number }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button>
        </form>
    </div>
</div>

{{-- Pratinjau memuat berkas PDF yang sama persis dengan yang diunduh dan
     dikirim ke customer, jadi tampilannya tidak mungkin berbeda. --}}
<div class="admin-card !p-0">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3">
        <p class="text-sm font-semibold text-ink">Pratinjau PDF</p>
        <a href="{{ route('admin.invoices.pdf', [$invoice, 'inline' => 1]) }}" target="_blank" rel="noopener"
           class="text-xs font-semibold text-brand-600 hover:underline">Buka di tab baru &rarr;</a>
    </div>
    <iframe src="{{ route('admin.invoices.pdf', [$invoice, 'inline' => 1]) }}"
            title="Pratinjau invoice {{ $invoice->invoice_number }}"
            class="block w-full rounded-b-xl bg-neutral-100"
            style="aspect-ratio: 297 / 210; min-height: 460px;"></iframe>
</div>

@php
    // Pesanan dengan beberapa invoice: pembayaran dihitung dari yang
    // ditautkan ke invoice ini, supaya tidak tercampur antar invoice.
    $multiInvoice = $invoice->order->invoices->count() > 1;
    $invoicePaid = $multiInvoice
        ? (int) $invoice->order->payments->where('invoice_id', $invoice->id)->sum('amount')
        : $invoice->order->amount_paid;
    $invoiceOutstanding = max(0, $invoice->grand_total - $invoicePaid);
@endphp

<div class="mt-5 grid gap-4 {{ $multiInvoice ? 'lg:grid-cols-2' : '' }}">
    <div class="admin-card">
        <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Invoice Ini</p>
        <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
            <div><p class="text-neutral-500">Tagihan</p><p class="mt-0.5 font-bold text-ink">{{ rupiah($invoice->grand_total) }}</p></div>
            <div><p class="text-neutral-500">Terbayar</p><p class="mt-0.5 font-bold text-green-600">{{ rupiah($invoicePaid) }}</p></div>
            <div><p class="text-neutral-500">Sisa</p><p class="mt-0.5 font-bold text-brand-600">{{ rupiah($invoiceOutstanding) }}</p></div>
        </div>
        @if($multiInvoice && $invoicePaid === 0)
            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Belum ada pembayaran yang ditautkan ke invoice ini. Tautkan lewat kolom <strong>Untuk Invoice</strong> di
                <a href="{{ route('admin.orders.show', ['order' => $invoice->order, 'tab' => 'payments']) }}" class="font-semibold underline">tab Pembayaran pesanan</a>.
            </p>
        @endif
    </div>

    @if($multiInvoice)
        <div class="admin-card">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Seluruh Pesanan ({{ $invoice->order->invoices->count() }} invoice)</p>
            <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                <div><p class="text-neutral-500">Terbayar</p><p class="mt-0.5 font-bold text-green-600">{{ rupiah($invoice->order->amount_paid) }}</p></div>
                <div><p class="text-neutral-500">Sisa</p><p class="mt-0.5 font-bold text-brand-600">{{ rupiah($invoice->order->remaining) }}</p></div>
                <div><p class="text-neutral-500">Status</p><div class="mt-1"><x-payment-badge :status="$invoice->order->payment_status" /></div></div>
            </div>
        </div>
    @endif
</div>
@endsection

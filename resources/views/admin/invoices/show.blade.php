@extends('layouts.admin')

@section('title', 'Invoice '.$invoice->invoice_number)

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.orders.show', ['order' => $invoice->order, 'tab' => 'invoice']) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
        <i class="fa-solid fa-shirt text-[10px]"></i>
        <span>Buka Pesanan</span>
    </a>
    <a href="{{ route('admin.invoices.edit', $invoice) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
        <i class="fa-solid fa-pen-to-square text-[10px]"></i>
        <span>Edit</span>
    </a>
    <x-admin.invoice-share :invoice="$invoice" />
    <a href="{{ route('admin.invoices.pdf', $invoice) }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-xs active:scale-95 transition-all">
        <i class="fa-solid fa-file-arrow-down text-[10px]"></i>
        <span>Unduh PDF</span>
    </a>
    <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus invoice {{ $invoice->invoice_number }}?')">
        @csrf @method('DELETE')
        <button type="submit" class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors" title="Hapus Invoice">
            <i class="fa-regular fa-trash-can text-xs"></i>
        </button>
    </form>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Invoice</span>
        </a>
    </div>

    <!-- TAUTAN INVOICE CARD -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xs shrink-0">
                <i class="fa-solid fa-link"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tautan Publik Invoice untuk Customer</p>
                <p class="mt-0.5 truncate font-mono text-xs text-brand-700 font-semibold" title="{{ $invoice->publicUrl() }}">{{ $invoice->publicUrl() }}</p>
            </div>
        </div>
        <div class="shrink-0" x-data="copyText(@js($invoice->publicUrl()))">
            <button type="button" x-on:click="copy()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                <i class="fa-regular fa-copy text-xs"></i>
                <span x-show="! copied">Salin tautan</span>
                <span x-show="copied" x-cloak>Tersalin!</span>
            </button>
        </div>
    </div>

    <!-- PRATINJAU PDF CARD -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <i class="fa-regular fa-file-pdf text-rose-600 text-base"></i>
                <h3 class="text-xs font-bold text-slate-900 tracking-tight">Pratinjau PDF</h3>
            </div>
            <a href="{{ route('admin.invoices.pdf', [$invoice, 'inline' => 1]) }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:underline">
                <span>Buka di tab baru</span>
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
        </div>
        <div class="bg-slate-100/60 p-2 sm:p-4">
            <iframe src="{{ route('admin.invoices.pdf', [$invoice, 'inline' => 1]) }}"
                    title="Pratinjau invoice {{ $invoice->invoice_number }}"
                    class="block w-full rounded-xl bg-white shadow-xs border border-slate-200"
                    style="aspect-ratio: 297 / 210; min-height: 480px;"></iframe>
        </div>
    </div>

    @php
        $multiInvoice = $invoice->order->invoices->count() > 1;
        $invoicePaid = $multiInvoice
            ? (int) $invoice->order->payments->where('invoice_id', $invoice->id)->sum('amount')
            : $invoice->order->amount_paid;
        $invoiceOutstanding = max(0, $invoice->grand_total - $invoicePaid);
    @endphp

    <!-- FINANCIAL SUMMARY METRICS -->
    <div class="grid gap-6 {{ $multiInvoice ? 'lg:grid-cols-2' : '' }}">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-700">Rincian Finansial Invoice Ini</p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-slate-50/70 rounded-xl p-3 sm:p-4">
                    <p class="text-[11px] text-slate-400">Total Tagihan</p>
                    <p class="mt-1 text-base sm:text-lg font-bold font-mono text-slate-900 truncate">{{ rupiah($invoice->grand_total) }}</p>
                </div>
                <div class="bg-slate-50/70 rounded-xl p-3 sm:p-4">
                    <p class="text-[11px] text-slate-400">Terbayar</p>
                    <p class="mt-1 text-base sm:text-lg font-bold font-mono text-emerald-600 truncate">{{ rupiah($invoicePaid) }}</p>
                </div>
                <div class="bg-slate-50/70 rounded-xl p-3 sm:p-4">
                    <p class="text-[11px] text-slate-400">Sisa Tagihan</p>
                    <p class="mt-1 text-base sm:text-lg font-bold font-mono {{ $invoiceOutstanding > 0 ? 'text-rose-600' : 'text-slate-400' }} truncate">{{ rupiah($invoiceOutstanding) }}</p>
                </div>
            </div>
            @if($multiInvoice && $invoicePaid === 0)
                <p class="mt-4 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">
                    Belum ada pembayaran yang ditautkan ke invoice ini. Tautkan lewat kolom <strong>Untuk Invoice</strong> di
                    <a href="{{ route('admin.orders.show', ['order' => $invoice->order, 'tab' => 'payments']) }}" class="font-semibold underline">tab Pembayaran pesanan</a>.
                </p>
            @endif
        </div>

        @if($multiInvoice)
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Seluruh Pesanan ({{ $invoice->order->invoices->count() }} invoice)</p>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-slate-50/70 rounded-xl p-3 sm:p-4">
                        <p class="text-[11px] text-slate-400">Total Terbayar</p>
                        <p class="mt-1 text-base sm:text-lg font-bold font-mono text-emerald-600 truncate">{{ rupiah($invoice->order->amount_paid) }}</p>
                    </div>
                    <div class="bg-slate-50/70 rounded-xl p-3 sm:p-4">
                        <p class="text-[11px] text-slate-400">Sisa Piutang</p>
                        <p class="mt-1 text-base sm:text-lg font-bold font-mono text-rose-600 truncate">{{ rupiah($invoice->order->remaining) }}</p>
                    </div>
                    <div class="bg-slate-50/70 rounded-xl p-3 sm:p-4">
                        <p class="text-[11px] text-slate-400">Status Pembayaran</p>
                        <div class="mt-1.5"><x-payment-badge :status="$invoice->order->payment_status" /></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

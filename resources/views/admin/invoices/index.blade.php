@extends('layouts.admin')

@section('title', 'Invoice')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex min-w-0 flex-1 gap-2 sm:flex-none">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="No. invoice / order..." class="form-input min-w-0 flex-1 sm:!w-64">
        <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
    </form>
    <a href="{{ route('admin.invoices.create') }}" class="btn-primary">+ Buat Invoice</a>
</div>

<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[760px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">No. Invoice</th>
                <th class="px-5 py-3">No. Order</th>
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3 text-right">Grand Total</th>
                <th class="px-5 py-3">Pembayaran</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($invoices as $invoice)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5"><a href="{{ route('admin.invoices.show', $invoice) }}" class="font-mono font-bold text-brand-600 hover:underline">{{ $invoice->invoice_number }}</a></td>
                    <td class="px-5 py-3.5"><a href="{{ route('admin.orders.show', $invoice->order) }}" class="font-mono text-neutral-600 hover:text-brand-600">{{ $invoice->order->order_number }}</a></td>
                    <td class="px-5 py-3.5">{{ $invoice->order->customer->name }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $invoice->date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3.5 text-right font-semibold">{{ rupiah($invoice->grand_total) }}</td>
                    <td class="px-5 py-3.5"><x-payment-badge :status="$invoice->order->payment_status" /></td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="text-sm font-semibold text-brand-600 hover:underline">PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-neutral-500">Belum ada invoice.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $invoices->links() }}</div>
@endsection

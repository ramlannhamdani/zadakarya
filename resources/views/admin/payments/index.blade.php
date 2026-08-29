@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('content')
<form method="GET" class="flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nomor order..." class="form-input min-w-0 flex-1 sm:!w-64">
    <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
</form>

<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[760px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">No. Order</th>
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3 text-right">Nominal</th>
                <th class="px-5 py-3">Metode</th>
                <th class="px-5 py-3">Invoice</th>
                <th class="px-5 py-3">Catatan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($payments as $payment)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5">{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3.5"><a href="{{ route('admin.orders.show', ['order' => $payment->order, 'tab' => 'payments']) }}" class="whitespace-nowrap font-mono font-bold text-brand-600 hover:underline">{{ $payment->order->order_number }}</a></td>
                    <td class="px-5 py-3.5">{{ $payment->order->customer->name }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold text-green-600">{{ rupiah($payment->amount) }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $payment->method_label }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $payment->invoice?->invoice_number ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $payment->note ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-neutral-500">Belum ada pembayaran tercatat.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $payments->links() }}</div>
@endsection

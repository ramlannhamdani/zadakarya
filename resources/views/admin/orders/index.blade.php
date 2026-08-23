@extends('layouts.admin')

@section('title', 'Pesanan')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1.5">
        @foreach([null => 'Semua', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $key => $label)
            <a href="{{ route('admin.orders.index', $key ? ['status' => $key] : []) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-semibold {{ $status === $key || (!$status && !$key) ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="flex gap-2">
        <form method="GET" class="flex gap-2">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="No. order / customer..." class="form-input !w-52">
            <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
        </form>
        <a href="{{ route('admin.orders.create') }}" class="btn-primary">+ Buat Pesanan</a>
    </div>
</div>

<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[860px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">No. Order</th>
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3">Produk</th>
                <th class="px-5 py-3">Total</th>
                <th class="px-5 py-3">Pembayaran</th>
                <th class="px-5 py-3">Tahap</th>
                <th class="px-5 py-3">Deadline</th>
                <th class="px-5 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($orders as $order)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5"><a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-brand-600 hover:underline">{{ $order->order_number }}</a></td>
                    <td class="px-5 py-3.5">
                        <span class="block font-medium text-ink">{{ $order->customer->name }}</span>
                        @if($order->customer->company)<span class="block text-xs text-neutral-500">{{ $order->customer->company }}</span>@endif
                    </td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $order->items->first()?->product_name }}{{ $order->items->count() > 1 ? ' +'.($order->items->count()-1).' item' : '' }}</td>
                    <td class="px-5 py-3.5 font-semibold">{{ rupiah($order->grand_total) }}</td>
                    <td class="px-5 py-3.5"><x-payment-badge :status="$order->payment_status" /></td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $order->current_stage }}/7 — {{ $order->current_stage_name }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $order->deadline?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-5 py-3.5"><x-order-status-badge :status="$order->status" /></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-5 py-8 text-center text-neutral-500">Tidak ada pesanan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $orders->links() }}</div>
@endsection

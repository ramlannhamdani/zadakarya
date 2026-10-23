@extends('layouts.admin')

@section('title', 'Pesanan')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1.5 items-center">
        @foreach([null => 'Semua', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $key => $label)
            <a href="{{ route('admin.orders.index', $key ? ['status' => $key] : []) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-semibold {{ (!$cashStatus && ($status === $key || (!$status && !$key))) ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600' }}">{{ $label }}</a>
        @endforeach
        @if(($deficitCount ?? 0) > 0)
            <a href="{{ route('admin.orders.index', ['cash_status' => 'deficit']) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-semibold border transition inline-flex items-center gap-1.5 {{ $cashStatus === 'deficit' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}"
               title="Pesanan aktif dengan belanja HPP melebihi DP masuk">
                <span>⚠️ Nombok Kas</span>
                <span class="rounded-full px-1.5 py-0.5 text-xs {{ $cashStatus === 'deficit' ? 'bg-white text-amber-700' : 'bg-amber-200 text-amber-900' }} font-bold">{{ $deficitCount }}</span>
            </a>
        @endif
    </div>
    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
        <form method="GET" class="flex min-w-0 flex-1 gap-2">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            @if($cashStatus)<input type="hidden" name="cash_status" value="{{ $cashStatus }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="No. order / customer..." class="form-input min-w-0 flex-1 sm:!w-52">
            <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
        </form>
        <a href="{{ route('admin.orders.create') }}" class="btn-primary">+ Buat Pesanan</a>
    </div>
</div>

{{-- Mobile & tablet portrait: kartu (2 kolom di md); tabel di lg+ --}}
<div class="mt-5 grid gap-3 md:grid-cols-2 lg:hidden">
    @forelse($orders as $order)
        <a href="{{ route('admin.orders.show', $order) }}" class="admin-card block !p-4">
            <div class="flex items-start justify-between gap-3">
                <span class="font-mono text-sm font-bold text-brand-600">{{ $order->order_number }}</span>
                <x-order-status-badge :status="$order->status" />
            </div>
            <p class="mt-1.5 font-semibold text-ink">{{ $order->customer->name }}@if($order->customer->company)<span class="font-normal text-neutral-500"> &bull; {{ $order->customer->company }}</span>@endif</p>
            <p class="text-xs text-neutral-500">{{ $order->items->first()?->product_name }}{{ $order->items->count() > 1 ? ' +'.($order->items->count()-1).' item' : '' }}</p>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-line pt-3 text-xs">
                <div>
                    <span class="font-bold text-ink">{{ rupiah($order->grand_total) }}</span>
                    @if($order->is_cash_deficit)
                        <span class="block mt-0.5 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200 w-fit">
                            ⚠️ Nombok {{ rupiah($order->cash_deficit) }}
                        </span>
                    @endif
                </div>
                <x-payment-badge :status="$order->payment_status" />
                <span class="text-neutral-500">{{ $order->current_stage }}/7 &bull; {{ $order->current_stage_name }}</span>
            </div>
            @if($order->deadline)<p class="mt-2 text-xs text-neutral-500">Deadline {{ $order->deadline->format('d/m/Y') }}</p>@endif
        </a>
    @empty
        <div class="admin-card py-8 text-center text-neutral-500 md:col-span-2">Tidak ada pesanan.</div>
    @endforelse
</div>
<div class="admin-card mt-5 hidden overflow-x-auto !p-0 lg:block">
    <table class="w-full min-w-[860px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="whitespace-nowrap px-5 py-3">No. Order</th>
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
                    <td class="px-5 py-3.5"><a href="{{ route('admin.orders.show', $order) }}" class="whitespace-nowrap font-mono font-bold text-brand-600 hover:underline">{{ $order->order_number }}</a></td>
                    <td class="px-5 py-3.5">
                        <span class="block font-medium text-ink">{{ $order->customer->name }}</span>
                        @if($order->customer->company)<span class="block text-xs text-neutral-500">{{ $order->customer->company }}</span>@endif
                    </td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $order->items->first()?->product_name }}{{ $order->items->count() > 1 ? ' +'.($order->items->count()-1).' item' : '' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 font-semibold">{{ rupiah($order->grand_total) }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex flex-col gap-1 items-start">
                            <x-payment-badge :status="$order->payment_status" />
                            @if($order->is_cash_deficit)
                                <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200" title="HPP Belanja ({{ rupiah($order->total_cost) }}) > DP Masuk ({{ rupiah($order->amount_paid) }})">
                                    <span>⚠️ Nombok</span>
                                    <span>{{ rupiah($order->cash_deficit) }}</span>
                                </span>
                            @elseif($order->amount_paid > 0 && $order->payment_status !== 'paid' && $order->total_cost > 0)
                                <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-1.5 py-0.5 text-[11px] font-medium text-blue-700 border border-blue-200" title="DP masih mencukupi belanja HPP">
                                    <span>Sisa DP {{ rupiah($order->cash_surplus) }}</span>
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $order->current_stage }}/7 — {{ $order->current_stage_name }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-neutral-600">{{ $order->deadline?->format('d/m/Y') ?? '—' }}</td>
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

@props(['order'])
@php
    $isPaid = $order->payment_status === 'paid';
    $position = $isPaid ? $order->net_cash_flow : $order->reported_dp_vs_hpp;
    $color = $position < 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : ($position > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200');
    $description = match (true) {
        $order->is_ready_to_bill => 'Sisa tagihan '.rupiah($order->remaining).' siap ditagih.',
        $isPaid => 'Pembayaran masuk '.rupiah($order->amount_paid).' dikurangi HPP '.rupiah($order->total_cost).'.',
        default => 'DP diterima '.rupiah($order->dp_paid).' dikurangi HPP '.rupiah($order->total_cost).'. Pelunasan dihitung terpisah.',
    };
@endphp
<span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-bold whitespace-nowrap {{ $color }}" title="{{ $description }}">
    {{ $position > 0 ? '+' : ($position < 0 ? '-' : '') }}{{ rupiah(abs($position)) }}
    @if($order->is_ready_to_bill)
        (Siap Ditagih)
    @elseif($isPaid)
        ({{ match ($position <=> 0) { -1 => 'Defisit Kas', 1 => 'Surplus Kas', 0 => 'Impas Kas' } }})
    @else
        ({{ $position < 0 ? ($order->is_cash_deficit ? 'Nombok' : 'DP Kurang') : ($position > 0 ? ($order->total_cost > 0 ? 'Aman DP' : 'DP Siap') : 'Impas DP') }})
    @endif
</span>

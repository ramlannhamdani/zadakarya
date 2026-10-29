@props(['order'])
@php
    $position = $order->dp_vs_hpp;
    $color = $position < 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : ($position > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200');
@endphp
<span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-bold whitespace-nowrap {{ $color }}" title="DP diterima {{ rupiah($order->dp_paid) }} dikurangi HPP {{ rupiah($order->total_cost) }}. Pelunasan dihitung terpisah.">
    {{ $position > 0 ? '+' : ($position < 0 ? '-' : '') }}{{ rupiah(abs($position)) }}
    ({{ $position < 0 ? ($order->is_cash_deficit ? 'Nombok' : 'DP Kurang') : ($position > 0 ? ($order->total_cost > 0 ? 'Aman DP' : 'DP Siap') : 'Impas DP') }})
</span>

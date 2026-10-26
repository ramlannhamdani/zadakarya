@props(['status'])
@php
    [$classes, $label] = match ($status) {
        'paid' => ['bg-emerald-50 text-emerald-700', 'Lunas'],
        'partial' => ['bg-amber-50 text-amber-700', 'DP'],
        default => ['bg-slate-100 text-slate-600', 'Belum Dibayar'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold '.$classes]) }}>{{ $label }}</span>

@props(['status'])
@php
    [$classes, $label] = match ($status) {
        'paid' => ['bg-green-100 text-green-700', 'Lunas'],
        'partial' => ['bg-amber-100 text-amber-700', 'DP'],
        default => ['bg-neutral-100 text-neutral-600', 'Belum Dibayar'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-block rounded-full px-2.5 py-0.5 text-xs font-bold '.$classes]) }}>{{ $label }}</span>

@props(['status'])
@php
    [$classes, $label] = match ($status) {
        'completed' => ['bg-emerald-50 text-emerald-700', 'Selesai'],
        'cancelled' => ['bg-rose-50 text-rose-700', 'Dibatalkan'],
        default => ['bg-amber-50 text-amber-700', 'Aktif'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold '.$classes]) }}>{{ $label }}</span>

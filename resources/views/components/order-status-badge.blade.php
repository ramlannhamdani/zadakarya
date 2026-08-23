@props(['status'])
@php
    [$classes, $label] = match ($status) {
        'completed' => ['bg-green-100 text-green-700', 'Selesai'],
        'cancelled' => ['bg-red-100 text-red-700', 'Dibatalkan'],
        default => ['bg-blue-100 text-blue-700', 'Aktif'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-block rounded-full px-2.5 py-0.5 text-xs font-bold '.$classes]) }}>{{ $label }}</span>

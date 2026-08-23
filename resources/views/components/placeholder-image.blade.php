@props(['label' => null, 'class' => ''])
<div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-gradient-to-br from-cream via-warm-300/40 to-brand-100 '.$class]) }}>
    <div class="flex flex-col items-center gap-1.5 text-brand-600/50">
        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3l2 2 2-2 2 2 2-2 2 2 2-2v13.5A2.5 2.5 0 0115.5 19h-7A2.5 2.5 0 016 16.5V3z" transform="translate(1.5 1)"/></svg>
        @if($label)<span class="text-[11px] font-semibold uppercase tracking-wider">{{ $label }}</span>@endif
    </div>
</div>

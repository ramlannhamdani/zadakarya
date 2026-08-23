@props(['service'])
<a href="{{ route('services.show', $service) }}" class="group flex flex-col overflow-hidden rounded-xl border border-line bg-white transition hover:border-brand-600/40 hover:shadow-sm">
    @if($service->featured_image)
        <img src="{{ asset('storage/'.$service->featured_image) }}" alt="{{ $service->name }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]">
    @else
        <x-placeholder-image :label="$service->name" class="aspect-[4/3] w-full" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <h3 class="font-bold text-ink group-hover:text-brand-600">{{ $service->name }}</h3>
        <p class="mt-1.5 line-clamp-2 flex-1 text-sm text-neutral-600">{{ $service->short_description }}</p>
        <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-600">
            Lihat Detail
            <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
        </span>
    </div>
</a>

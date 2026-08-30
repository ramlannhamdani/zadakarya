@props(['clients'])

{{-- Carousel logo klien: geser per satu layar penuh, dengan tombol panah dan
     indikator titik. Tombol & titik disembunyikan kalau semua logo sudah muat. --}}
<div x-data="clientCarousel" x-init="measure()" {{ $attributes->merge(['class' => '']) }}>
    {{-- Wrapper terpisah supaya tombol sejajar dengan baris logo, bukan ikut turun karena titik --}}
    <div class="relative">
        <button type="button" @click="prev()" x-show="pages > 1" x-cloak :disabled="page === 0"
                class="absolute -left-1 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-line bg-white text-brand-600 shadow-[0_8px_20px_-10px_rgba(32,32,32,.4)] transition hover:border-brand-600 disabled:opacity-40 sm:h-12 sm:w-12 lg:-left-2"
                aria-label="Logo sebelumnya">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>

        <div class="px-12 sm:px-14">
            <div x-ref="track" @scroll.debounce.120ms="sync()" class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
                @foreach($clients as $client)
                    @php $logo = asset('storage/'.$client->logo_path); @endphp
                    <div class="flex w-1/2 shrink-0 snap-start items-center justify-center px-3 sm:w-1/3 md:w-1/4 lg:w-1/6">
                        @if($client->website_url)
                            <a href="{{ $client->website_url }}" target="_blank" rel="noopener" title="{{ $client->name }}">
                                <img src="{{ $logo }}" alt="{{ $client->name }}" loading="lazy"
                                     class="h-12 w-auto max-w-full object-contain opacity-60 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0 sm:h-14">
                            </a>
                        @else
                            <img src="{{ $logo }}" alt="{{ $client->name }}" loading="lazy" title="{{ $client->name }}"
                                 class="h-12 w-auto max-w-full object-contain opacity-60 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0 sm:h-14">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <button type="button" @click="next()" x-show="pages > 1" x-cloak :disabled="page >= pages - 1"
                class="absolute -right-1 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-line bg-white text-brand-600 shadow-[0_8px_20px_-10px_rgba(32,32,32,.4)] transition hover:border-brand-600 disabled:opacity-40 sm:h-12 sm:w-12 lg:-right-2"
                aria-label="Logo berikutnya">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <div class="mt-8 flex items-center justify-center gap-2.5" x-show="pages > 1" x-cloak>
        <template x-for="i in pages" :key="i">
            <button type="button" @click="go(i - 1)"
                    :class="page === i - 1 ? 'border-brand-600 bg-brand-600' : 'border-brand-600/40 bg-transparent hover:border-brand-600'"
                    class="h-2.5 w-2.5 rounded-full border-2 transition"
                    :aria-label="'Ke halaman logo ' + i"></button>
        </template>
    </div>
</div>

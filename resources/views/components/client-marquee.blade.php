@props(['clients'])
{{-- Carousel logo klien: dua salinan daftar digulir -50% agar loop mulus; jeda saat hover. --}}
<div {{ $attributes->merge(['class' => 'marquee relative overflow-hidden']) }}>
    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-12 bg-gradient-to-r from-white to-transparent sm:w-20"></div>
    <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 bg-gradient-to-l from-white to-transparent sm:w-20"></div>
    <div class="marquee-track flex w-max" style="animation-duration: {{ max(24, $clients->count() * 5) }}s">
        @foreach([false, true] as $duplicate)
            <div class="flex shrink-0 items-center gap-12 pr-12 sm:gap-16 sm:pr-16" @if($duplicate) aria-hidden="true" @endif>
                @foreach($clients as $client)
                    @if($client->website_url)
                        <a href="{{ $client->website_url }}" target="_blank" rel="noopener" class="shrink-0" title="{{ $client->name }}">
                            <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" loading="lazy" class="h-9 w-auto max-w-[150px] object-contain opacity-70 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0 sm:h-11 sm:max-w-[180px]">
                        </a>
                    @else
                        <span class="shrink-0" title="{{ $client->name }}">
                            <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" loading="lazy" class="h-9 w-auto max-w-[150px] object-contain opacity-70 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0 sm:h-11 sm:max-w-[180px]">
                        </span>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</div>

@props(['reviews'])
@php $avg = round($reviews->avg('rating'), 1); @endphp
<div x-data>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-warm-600">
                {{-- Google G logo --}}
                <svg class="h-4 w-4" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/><path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/><path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0124 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/><path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/></svg>
                Ulasan dari Google Maps
            </p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-ink">Kata Mereka Tentang Kami</h2>
            <div class="mt-2.5 flex items-center gap-2 text-sm">
                <span class="text-lg font-extrabold text-ink">{{ number_format($avg, 1, ',', '.') }}</span>
                <span class="flex text-amber-400">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="h-4.5 w-4.5 {{ $i <= round($avg) ? '' : 'text-neutral-300' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                    @endfor
                </span>
                <span class="text-neutral-500">({{ $reviews->count() }} ulasan)</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if(setting('google_maps_url'))
                <a href="{{ setting('google_maps_url') }}" target="_blank" rel="noopener" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua di Google Maps &rarr;</a>
            @endif
            <div class="flex gap-2">
                <button type="button" aria-label="Sebelumnya"
                        @click="$refs.track.scrollBy({ left: -$refs.track.clientWidth * 0.9, behavior: 'smooth' })"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-white text-neutral-600 transition hover:border-brand-600 hover:text-brand-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" aria-label="Berikutnya"
                        @click="$refs.track.scrollBy({ left: $refs.track.clientWidth * 0.9, behavior: 'smooth' })"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-white text-neutral-600 transition hover:border-brand-600 hover:text-brand-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div x-ref="track" class="no-scrollbar -mx-1 mt-8 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-1 pb-1">
        @foreach($reviews as $review)
            <article class="w-[86%] shrink-0 snap-start rounded-xl border border-line bg-white p-6 sm:w-[47%] lg:w-[31.5%]">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-base font-bold text-white {{ $review->avatar_class }}">{{ $review->initial }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-bold text-ink">{{ $review->author_name }}</p>
                        <p class="text-xs text-neutral-500">{{ $review->review_date?->translatedFormat('d F Y') }}</p>
                    </div>
                    <svg class="ml-auto h-5 w-5 shrink-0" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/><path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/><path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0124 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/><path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/></svg>
                </div>
                <div class="mt-3 flex text-amber-400">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="h-4 w-4 {{ $i <= $review->rating ? '' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                    @endfor
                </div>
                <p class="mt-3 line-clamp-5 text-sm leading-relaxed text-neutral-600">{{ $review->content }}</p>
            </article>
        @endforeach
    </div>
</div>

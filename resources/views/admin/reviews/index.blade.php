@extends('layouts.admin')

@section('title', 'Ulasan Google')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-neutral-500">Salin ulasan dari halaman Google Maps Anda, lalu tambahkan di sini agar tampil pada carousel di halaman Portfolio.</p>
    <a href="{{ route('admin.reviews.create') }}" class="btn-primary">+ Tambah Ulasan</a>
</div>

<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[720px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Nama</th>
                <th class="px-5 py-3">Rating</th>
                <th class="px-5 py-3">Ulasan</th>
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($reviews as $review)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white {{ $review->avatar_class }}">{{ $review->initial }}</span>
                            <span class="font-semibold text-ink">{{ $review->author_name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="flex text-amber-400">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="h-3.5 w-3.5 {{ $i <= $review->rating ? '' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                            @endfor
                        </span>
                    </td>
                    <td class="max-w-[320px] px-5 py-3.5 text-neutral-600"><span class="line-clamp-2">{{ $review->content }}</span></td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $review->review_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-5 py-3.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $review->is_published ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">
                            {{ $review->is_published ? 'Tampil' : 'Disembunyikan' }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.reviews.edit', $review) }}" class="text-xs font-semibold text-brand-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Hapus ulasan dari {{ $review->author_name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-500">Belum ada ulasan. Tambahkan ulasan dari Google Maps Anda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $reviews->links() }}</div>
@endsection

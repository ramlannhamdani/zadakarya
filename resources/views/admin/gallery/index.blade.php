@extends('layouts.admin')

@section('title', 'Galeri')

@section('content')
<form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="admin-card">
    @csrf
    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-64 flex-1">
            <label class="form-label" for="photos">Upload Gambar (bisa lebih dari satu)</label>
            <input class="form-input !py-2" type="file" id="photos" name="photos[]" accept="image/*" multiple required>
            <p class="mt-1 text-xs text-neutral-500">JPG/PNG/WebP, maks 8 MB per gambar. Tanpa judul/deskripsi — langsung tampil di halaman <a href="{{ route('gallery.index') }}" target="_blank" class="font-semibold text-brand-600 hover:underline">/galeri</a>.</p>
        </div>
        <button type="submit" class="btn-primary">Upload</button>
    </div>
</form>

<div class="mt-5">
    @if($items->isEmpty())
        <div class="admin-card py-12 text-center text-neutral-500">Belum ada gambar di galeri.</div>
    @else
        <p class="mb-3 text-sm text-neutral-500">{{ $items->total() }} gambar</p>
        <div class="columns-2 gap-4 sm:columns-4 lg:columns-5">
            @foreach($items as $item)
                <div class="group relative mb-4 break-inside-avoid overflow-hidden rounded-lg border border-line">
                    <img src="{{ asset('storage/'.($item->thumb_path ?: $item->image_path)) }}" alt="" loading="lazy" class="w-full {{ $item->is_public ? '' : 'opacity-50' }}">

                    {{-- Toggle tampil/sembunyi di galeri publik --}}
                    <form method="POST" action="{{ route('admin.gallery.toggle', $item) }}" class="absolute left-2 top-2">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="rounded-full px-2.5 py-1 text-[11px] font-bold shadow transition {{ $item->is_public ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-neutral-200 text-neutral-600 hover:bg-neutral-300' }}"
                                title="Klik untuk {{ $item->is_public ? 'sembunyikan dari' : 'tampilkan di' }} galeri publik">
                            {{ $item->is_public ? '● Publik' : '○ Disembunyikan' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.gallery.destroy', $item) }}"
                          onsubmit="return confirm('Hapus gambar ini dari galeri?')"
                          class="absolute right-2 top-2 opacity-0 transition group-hover:opacity-100">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-full bg-white/95 p-2 text-red-500 shadow hover:bg-white" title="Hapus">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="mt-5">{{ $items->links() }}</div>
    @endif
</div>
@endsection

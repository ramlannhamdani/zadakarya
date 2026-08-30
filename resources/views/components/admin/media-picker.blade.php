@props([
    'name',
    'multiple' => false,
    'current' => null,   // path gambar yang sedang terpasang; memunculkan preview + tombol hapus
])
@php $pickName = $name.'_pick'.($multiple ? '[]' : ''); @endphp
<div x-data="mediaPicker({
        pickerUrl: '{{ route('admin.gallery.picker') }}',
        uploadUrl: '{{ route('admin.gallery.store') }}',
        csrf: '{{ csrf_token() }}',
        multiple: {{ $multiple ? 'true' : 'false' }}
    })">

    @if($current && ! $multiple)
        {{-- Gambar terpasang: bisa ditimpa (pilih file baru) atau dikosongkan lewat tombol X --}}
        <div class="mb-2 flex items-start gap-2" x-show="! removed">
            <div class="min-w-0">
                @if($slot->isEmpty())
                    <img src="{{ asset('storage/'.$current) }}" alt="Gambar saat ini" class="h-16 w-auto max-w-full rounded-lg border border-line object-contain">
                @else
                    {{ $slot }}
                @endif
            </div>
            <button type="button" @click="removed = true" title="Hapus gambar ini"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-line bg-white text-neutral-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                <span class="sr-only">Hapus gambar</span>
            </button>
        </div>

        <div class="mb-2 flex flex-wrap items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs" x-show="removed" x-cloak>
            <input type="hidden" name="remove_{{ $name }}" value="1">
            <span class="font-semibold text-red-700">Gambar akan dihapus saat disimpan.</span>
            <button type="button" @click="removed = false" class="font-semibold text-neutral-600 underline hover:text-ink">Batalkan</button>
        </div>
    @endif

    <div class="flex gap-2">
        <input class="form-input flex-1 !py-2" type="file"
               name="{{ $multiple ? $name.'[]' : $name }}"
               @if($multiple) multiple @endif
               accept="image/*" x-ref="file" @change="onFileChange()">
        <button type="button" @click="open()"
                class="btn-outline whitespace-nowrap !px-3.5 !py-2 text-xs">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
            Galeri
        </button>
    </div>

    {{-- Preview pilihan dari galeri + hidden input yang dikirim ke server --}}
    <div class="mt-2 flex flex-wrap items-center gap-2" x-show="picked.length" x-cloak>
        <template x-for="p in picked" :key="p.id">
            <span>
                <img :src="p.thumb" class="h-14 w-14 rounded-lg border border-line object-cover" alt="">
                <input type="hidden" name="{{ $pickName }}" :value="p.id">
            </span>
        </template>
        <button type="button" @click="clearPicks()" class="text-xs font-semibold text-red-500 hover:underline">Batal pilih</button>
    </div>

    {{-- Popup galeri --}}
    <template x-teleport="body">
        <div x-show="openModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="openModal = false"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <h3 class="font-extrabold text-ink">Pilih dari Galeri</h3>
                    <div class="flex items-center gap-2">
                        <label class="btn-primary cursor-pointer !px-4 !py-2 text-xs" :class="uploading && 'opacity-50'">
                            <span x-show="!uploading">+ Upload Baru</span>
                            <span x-show="uploading" x-cloak>Mengunggah...</span>
                            <input type="file" class="hidden" accept="image/*" multiple @change="upload($event)" :disabled="uploading">
                        </label>
                        <button type="button" @click="openModal = false" class="rounded-lg p-2 text-neutral-500 hover:bg-cream" aria-label="Tutup">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-5">
                    <div x-show="loaded && !items.length" class="py-10 text-center text-sm text-neutral-500">
                        Galeri masih kosong — klik <span class="font-semibold">+ Upload Baru</span> untuk menambahkan gambar.
                    </div>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                        <template x-for="item in items" :key="item.id">
                            <button type="button" @click="toggle(item.id)"
                                    class="relative overflow-hidden rounded-lg border border-line transition"
                                    :class="selected.includes(item.id) && 'ring-2 ring-brand-600 ring-offset-1'">
                                <img :src="item.thumb" class="aspect-square w-full object-cover" alt="" loading="lazy">
                                <span x-show="selected.includes(item.id)" x-cloak
                                      class="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-white">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-line px-5 py-4">
                    <span class="text-sm text-neutral-500" x-text="selected.length ? selected.length + ' dipilih' : 'Klik gambar untuk memilih'"></span>
                    <div class="flex gap-2">
                        <button type="button" @click="openModal = false" class="btn-outline !px-4 !py-2 text-xs">Batal</button>
                        <button type="button" @click="use()" :disabled="!selected.length"
                                class="btn-primary !px-4 !py-2 text-xs" :class="!selected.length && 'opacity-50'">
                            Gunakan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

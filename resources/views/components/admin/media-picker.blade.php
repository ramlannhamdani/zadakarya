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
        <div class="mb-3 flex items-start gap-3" x-show="! removed">
            <div class="min-w-0">
                @if($slot->isEmpty())
                    <img src="{{ asset('storage/'.$current) }}" alt="Gambar saat ini" class="h-20 w-auto max-w-full rounded-xl border border-slate-200 object-contain shadow-xs">
                @else
                    {{ $slot }}
                @endif
            </div>
            <button type="button" @click="removed = true" title="Hapus gambar ini"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                <i class="fa-solid fa-xmark text-xs"></i>
                <span class="sr-only">Hapus gambar</span>
            </button>
        </div>

        {{-- WAJIB template x-if, bukan x-show: elemen ber-display:none tetap ikut
             terkirim saat form disubmit, sehingga semua gambar akan terhapus. --}}
        <template x-if="removed">
            <div class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-rose-50 border border-rose-100 px-3 py-2 text-xs">
                <input type="hidden" name="remove_{{ $name }}" value="1">
                <span class="font-semibold text-rose-700">Gambar akan dihapus saat form disimpan.</span>
                <button type="button" @click="removed = false" class="font-semibold text-slate-600 underline hover:text-slate-900 ml-1">Batalkan</button>
            </div>
        </template>
    @endif

    <div class="flex gap-2">
        <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2 text-xs text-slate-700 file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 transition-colors flex-1"
               type="file"
               name="{{ $multiple ? $name.'[]' : $name }}"
               @if($multiple) multiple @endif
               accept="image/*" x-ref="file" @change="onFileChange()">
        <button type="button" @click="open()"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold whitespace-nowrap transition-colors">
            <i class="fa-regular fa-images text-xs text-slate-500"></i>
            <span>Pilih Galeri</span>
        </button>
    </div>

    {{-- Preview pilihan dari galeri + hidden input yang dikirim ke server --}}
    <div class="mt-3 flex flex-wrap items-center gap-2.5" x-show="picked.length" x-cloak>
        <template x-for="p in picked" :key="p.id">
            <span class="relative group">
                <img :src="p.thumb" class="h-14 w-14 rounded-xl border border-slate-200 object-cover shadow-xs" alt="">
                <input type="hidden" name="{{ $pickName }}" :value="p.id">
            </span>
        </template>
        <button type="button" @click="clearPicks()" class="text-xs font-semibold text-rose-600 hover:underline ml-1">Batal pilih</button>
    </div>

    {{-- Popup galeri --}}
    <template x-teleport="body">
        <div x-show="openModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" @click="openModal = false"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-xs shrink-0">
                            <i class="fa-regular fa-images"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Pilih Media dari Galeri</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs cursor-pointer active:scale-95 transition-all" :class="uploading && 'opacity-50'">
                            <i class="fa-solid fa-arrow-up-from-bracket text-[10px]" x-show="!uploading"></i>
                            <span x-show="!uploading">Upload Foto Baru</span>
                            <span x-show="uploading" x-cloak>Mengunggah...</span>
                            <input type="file" class="hidden" accept="image/*" multiple @change="upload($event)" :disabled="uploading">
                        </label>
                        <button type="button" @click="openModal = false"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors" aria-label="Tutup">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div x-show="loaded && !items.length" class="py-12 text-center text-xs text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Galeri masih kosong — klik <strong class="text-slate-700 font-semibold">Upload Foto Baru</strong> untuk menambahkan gambar.
                    </div>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                        <template x-for="item in items" :key="item.id">
                            <button type="button" @click="toggle(item.id)"
                                    class="relative overflow-hidden rounded-xl border border-slate-200 transition-all hover:scale-102"
                                    :class="selected.includes(item.id) && 'ring-2 ring-brand-600 ring-offset-2'">
                                <img :src="item.thumb" class="aspect-square w-full object-cover" alt="" loading="lazy">
                                <span x-show="selected.includes(item.id)" x-cloak
                                      class="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-white shadow-xs">
                                    <i class="fa-solid fa-check text-[10px]"></i>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-6 py-4 bg-slate-50/50">
                    <span class="text-xs text-slate-500 font-medium" x-text="selected.length ? selected.length + ' foto dipilih' : 'Klik foto untuk memilih'"></span>
                    <div class="flex gap-2">
                        <button type="button" @click="openModal = false" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">Batal</button>
                        <button type="button" @click="use()" :disabled="!selected.length"
                                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all"
                                :class="!selected.length && 'opacity-50 cursor-not-allowed'">
                            <i class="fa-solid fa-check text-[10px]"></i>
                            <span>Gunakan Pilihan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

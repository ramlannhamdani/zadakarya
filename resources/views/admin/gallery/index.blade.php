@extends('layouts.admin')

@section('title', 'Galeri Foto & Dokumentasi Produksi')

@section('header-actions')
<button type="button" onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i>
    <span>Upload Foto</span>
</button>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Total Foto -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Gambar Galeri</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</span>
                <span class="text-[11px] text-slate-400">foto</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Asset visual website</p>
        </div>

        <!-- Tayang di Publik -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Muncul di Publik (Live)</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($stats['public']) }}</span>
                <span class="text-[11px] text-emerald-600/80 font-medium truncate">terlihat pengunjung</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Tayang di galeri website</p>
        </div>

        <!-- Disembunyikan (Hidden) -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60 col-span-2 sm:col-span-1">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Disembunyikan (Arsip)</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-700">{{ number_format($stats['hidden']) }}</span>
                <span class="text-[11px] text-slate-400 font-medium">hanya admin</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Tidak tampil di website</p>
        </div>
    </section>

    <!-- 2. TOOLBAR FILTER & SEARCH -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Status Tab Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs no-scrollbar">
                <a href="{{ route('admin.gallery.index') }}" class="px-3.5 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('status') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua Gambar ({{ $stats['total'] }})
                </a>
                <a href="{{ route('admin.gallery.index', ['status' => 'public']) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'public' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Publik ({{ $stats['public'] }})
                </a>
                <a href="{{ route('admin.gallery.index', ['status' => 'hidden']) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'hidden' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Disembunyikan ({{ $stats['hidden'] }})
                </a>
            </div>

            <!-- Upload Button Secondary -->
            <button type="button" onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold flex items-center gap-2 transition-colors shrink-0">
                <i class="fa-solid fa-plus text-slate-400 text-xs"></i>
                <span>Tambah Foto</span>
            </button>

        </div>
    </section>

    <!-- 3. RESPONSIVE IMAGE GRID -->
    <section class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4">
        @forelse($items as $item)
            @php
                $isPublished = $item->is_public;
                $imageUrl = asset('storage/'.($item->thumb_path ?: $item->image_path));
            @endphp
            <div class="group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col justify-between border border-transparent hover:border-slate-100">
                <!-- Image Wrapper -->
                <div class="relative aspect-square w-full bg-slate-100 overflow-hidden">
                    <img src="{{ $imageUrl }}" alt="Galeri #{{ $item->id }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 {{ $isPublished ? '' : 'opacity-60' }}">
                    
                    <!-- Status Badge Overlay -->
                    <div class="absolute top-2.5 left-2.5">
                        @if($isPublished)
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/95 backdrop-blur-xs text-emerald-700 text-[10px] font-bold shadow-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Publik
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-900/80 backdrop-blur-xs text-slate-200 text-[10px] font-semibold">
                                <i class="fa-solid fa-eye-slash text-[9px]"></i> Disembunyikan
                            </span>
                        @endif
                    </div>

                    <!-- Hover Quick Action Overlay -->
                    <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <!-- Toggle Form -->
                        <form method="POST" action="{{ route('admin.gallery.toggle', $item) }}">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $isPublished ? 'Sembunyikan dari Publik' : 'Tampilkan ke Publik' }}" class="w-8 h-8 rounded-xl {{ $isPublished ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white flex items-center justify-center text-xs transition-colors shadow-sm">
                                <i class="fa-solid {{ $isPublished ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                            </button>
                        </form>

                        <!-- Delete Form -->
                        <form method="POST" action="{{ route('admin.gallery.destroy', $item) }}" onsubmit="return confirm('Hapus gambar ini dari galeri?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Hapus Gambar" class="w-8 h-8 rounded-xl bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center text-xs transition-colors shadow-sm">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Bottom Meta Bar -->
                <div class="p-3 flex items-center justify-between text-xs bg-white">
                    <span class="font-semibold text-slate-800 truncate text-[11px]">Foto #{{ $item->id }}</span>
                    <span class="text-[10px] text-slate-400 font-mono shrink-0">{{ $item->created_at?->format('d/m/Y') }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl shadow-sm p-8">
                <i class="fa-regular fa-image text-3xl mb-2 block text-slate-300"></i>
                Tidak ada gambar yang cocok dengan filter.
            </div>
        @endforelse
    </section>

    <!-- Footer Pagination -->
    @if($items->hasPages())
        <div class="p-4 sm:px-6 bg-white rounded-2xl shadow-sm">
            {{ $items->links() }}
        </div>
    @endif

</div>

<div id="uploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl relative animate-in fade-in zoom-in-95 duration-150">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Upload Gambar Baru</h3>
                <p class="text-xs text-slate-400 mt-0.5">Tambahkan foto ke galeri dokumentasi website.</p>
            </div>
            <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            
            <!-- File Input Area -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Pilih Berkas Foto</label>
                <div class="border-2 border-dashed border-slate-200 hover:border-slate-400 rounded-2xl p-6 text-center transition-colors bg-slate-50/50 relative">
                    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-white text-brand-600 shadow-xs flex items-center justify-center mx-auto text-lg mb-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <span class="font-bold text-slate-800 block text-xs">Klik untuk pilih berkas foto (bisa banyak)</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">JPG, PNG, WEBP hingga 8MB per file</span>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold text-xs transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs">
                    Upload ke Galeri
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

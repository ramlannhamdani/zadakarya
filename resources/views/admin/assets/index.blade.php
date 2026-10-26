@extends('layouts.admin')

@section('title', 'Aset & Valuasi Konveksi')

@section('header-actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-all">
        <i class="fa-solid fa-scale-balanced text-[11px]"></i>
        <span>Buka Neraca</span>
    </a>
    <button type="button" @click="$dispatch('open-create-asset-modal')"
            class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Tambah Aset</span>
    </button>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{
    createModal: false,
    editModal: false,
    editForm: {
        id: null,
        code: '',
        name: '',
        category: 'machinery',
        purchase_date: '',
        quantity: 1,
        unit: 'unit',
        purchase_price: 0,
        current_value: 0,
        condition: 'good',
        location: '',
        notes: ''
    },
    openEdit(asset) {
        this.editForm = {
            id: asset.id,
            code: asset.code || '',
            name: asset.name,
            category: asset.category,
            purchase_date: asset.purchase_date ? asset.purchase_date.substring(0, 10) : '',
            quantity: asset.quantity,
            unit: asset.unit || 'unit',
            purchase_price: asset.purchase_price,
            current_value: asset.current_value,
            condition: asset.condition,
            location: asset.location || '',
            notes: asset.notes || ''
        };
        this.editModal = true;
    }
}" @open-create-asset-modal.window="createModal = true">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total Valuasi Saat Ini -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Valuasi Aset Saat Ini</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-emerald-600 truncate">{{ rupiah($stats['total_current_value']) }}</span>
            </div>
            <p class="text-[11px] text-emerald-600/80 font-medium mt-0.5 truncate">Total nilai pasar inventaris</p>
        </div>

        <!-- Total Harga Perolehan -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Harga Beli / Perolehan</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-slate-900 truncate">{{ rupiah($stats['total_purchase_price']) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Akumulasi modal beli awal</p>
        </div>

        <!-- Total Unit Aset -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Kuantitas Aset</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total_units']) }}</span>
                <span class="text-[11px] text-slate-400">unit ({{ $stats['total_items'] }} item)</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $stats['good_condition_count'] }} item kondisi prima</p>
        </div>

        <!-- Estimasi Penyusutan / Depresiasi -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Akumulasi Depresiasi</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-xl sm:text-2xl font-bold text-slate-600 truncate">{{ rupiah($stats['total_depreciation']) }}</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Penurunan nilai pemakaian</p>
        </div>
    </section>

    <!-- 2. FILTER & ACTION TOOLBAR -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        <form method="GET" action="{{ route('admin.assets.index') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Category Tab Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs no-scrollbar">
                <a href="{{ route('admin.assets.index', request()->except('category')) }}"
                   class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !$activeCategory || $activeCategory === 'all' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua Kategori ({{ $stats['total_items'] }})
                </a>
                @foreach($categories as $catKey => $catLabel)
                    <a href="{{ route('admin.assets.index', array_merge(request()->query(), ['category' => $catKey])) }}"
                       class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ $activeCategory === $catKey ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        {{ $catLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Search & Condition Dropdown Filter -->
            <div class="flex items-center gap-2 w-full lg:w-auto">
                <div class="relative flex-1 sm:w-64 sm:flex-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama aset, kode, lokasi..."
                           class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
                </div>

                <select name="condition" onchange="this.form.submit()" class="bg-slate-50 text-xs text-slate-700 rounded-xl px-3 py-2 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300 cursor-pointer shrink-0">
                    <option value="all">Semua Kondisi</option>
                    @foreach($conditions as $cKey => $cLabel)
                        <option value="{{ $cKey }}" @selected(request('condition') === $cKey)>{{ $cLabel }}</option>
                    @endforeach
                </select>

                <button type="submit" class="shrink-0 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Cari
                </button>
                @if(request()->hasAny(['q', 'category', 'condition']))
                    <a href="{{ route('admin.assets.index') }}" class="shrink-0 px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors">
                        Reset
                    </a>
                @endif
            </div>

        </form>
    </section>

    <!-- 3. DATA TABLE ASET KONVEKSI -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">KODE & NAMA ASET</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider">KATEGORI</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">TGL BELI</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">JUMLAH</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-right">HARGA BELI</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-right">VALUASI SAAT INI</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">KONDISI</th>
                        <th class="py-3.5 px-3.5 sm:px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($assets as $asset)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <!-- 1. KODE & NAMA -->
                        <td class="py-3.5 px-3.5 sm:px-5">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold bg-slate-100 text-slate-700 shrink-0">
                                    {{ $asset->code }}
                                </span>
                                <span class="font-bold text-slate-900 block truncate max-w-xs sm:max-w-sm">{{ $asset->name }}</span>
                            </div>
                            @if($asset->location)
                                <p class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-[9px] text-slate-400"></i>
                                    <span>{{ $asset->location }}</span>
                                </p>
                            @endif
                        </td>

                        <!-- 2. KATEGORI -->
                        <td class="py-3.5 px-3.5 sm:px-5">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $asset->category_color }}">
                                {{ $asset->category_label }}
                            </span>
                        </td>

                        <!-- 3. TGL BELI -->
                        <td class="py-3.5 px-3.5 sm:px-5 text-center text-slate-500 font-mono text-[11px]">
                            {{ $asset->purchase_date ? $asset->purchase_date->format('d/m/Y') : '—' }}
                        </td>

                        <!-- 4. JUMLAH -->
                        <td class="py-3.5 px-3.5 sm:px-5 text-center">
                            <span class="font-bold text-slate-800">{{ $asset->quantity }}</span>
                            <span class="text-slate-400 text-[10px] ml-0.5">{{ $asset->unit }}</span>
                        </td>

                        <!-- 5. HARGA BELI -->
                        <td class="py-3.5 px-3.5 sm:px-5 text-right font-mono text-slate-600">
                            {{ rupiah($asset->purchase_price) }}
                        </td>

                        <!-- 6. ESTIMASI VALUASI SAAT INI -->
                        <td class="py-3.5 px-3.5 sm:px-5 text-right font-mono font-bold text-slate-900">
                            {{ rupiah($asset->current_value) }}
                            @if($asset->depreciation_amount > 0)
                                <span class="block text-[10px] font-normal text-rose-500 font-sans">
                                    -{{ rupiah($asset->depreciation_amount) }} ({{ $asset->depreciation_percentage }}%)
                                </span>
                            @endif
                        </td>

                        <!-- 7. KONDISI -->
                        <td class="py-3.5 px-3.5 sm:px-5 text-center">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $asset->condition_color }}">
                                {{ $asset->condition_label }}
                            </span>
                        </td>

                        <!-- 8. AKSI -->
                        <td class="py-3.5 px-3.5 sm:px-5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" @click="openEdit({{ json_encode($asset) }})" title="Edit Aset"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-brand-700 hover:bg-slate-100 transition-colors">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>

                                <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}"
                                      onsubmit="return confirm('Hapus aset {{ $asset->name }} ({{ $asset->code }}) dari daftar valuasi?')" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Hapus Aset" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-boxes-stacked text-3xl mb-2 block text-slate-300"></i>
                            Belum ada data aset konveksi yang sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        @if($assets->hasPages() || $assets->total() > 0)
        <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
            <span>Menampilkan {{ $assets->count() }} dari {{ $assets->total() }} aset terdaftar</span>
            <div>
                {{ $assets->links() }}
            </div>
        </div>
        @endif
    </section>

    <!-- MODAL 1: TAMBAH ASET BARU -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.away="createModal = false"
             class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-5 sm:p-7 shadow-2xl relative animate-in fade-in zoom-in-95 duration-150">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Catat Aset Konveksi Baru</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Mesin, peralatan sablon, cutting, atau fasilitas workshop</p>
                </div>
                <button type="button" @click="createModal = false" class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.assets.store') }}" class="space-y-4 text-xs">
                @csrf

                <!-- Nama Aset -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Aset / Mesin / Alat <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Mesin Jahit High-Speed Juki DDL-8100e"
                           class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none">
                </div>

                <!-- Kategori & Kondisi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori Aset <span class="text-rose-500">*</span></label>
                        <select name="category" required class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                            @foreach($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kondisi Saat Ini <span class="text-rose-500">*</span></label>
                        <select name="condition" required class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                            @foreach($conditions as $cKey => $cLabel)
                                <option value="{{ $cKey }}">{{ $cLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Qty, Satuan & Tgl Pembelian -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jumlah (Qty) <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" min="1" value="1" required
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Satuan</label>
                        <input type="text" name="unit" value="unit" placeholder="unit, set, pcs"
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tgl Pembelian</label>
                        <input type="date" name="purchase_date" value="{{ now()->toDateString() }}"
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>
                </div>

                <!-- Harga Beli & Estimasi Valuasi Saat Ini -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Harga Beli / Perolehan (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="purchase_price" min="0" required placeholder="Contoh: 15000000"
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Estimasi Valuasi Saat Ini (Rp)</label>
                        <input type="number" name="current_value" min="0" placeholder="Kosongkan jika sama dengan harga beli"
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>
                </div>

                <!-- Lokasi Workshop -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lokasi / Ruang Workshop</label>
                    <input type="text" name="location" placeholder="Contoh: Lantai 1 Jalur Jahit, Ruang Sablon Belakang"
                           class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none">
                </div>

                <!-- Catatan -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan / Spesifikasi</label>
                    <textarea name="notes" rows="2" placeholder="Catatan spesifikasi, riwayat servis, daya listrik, dll..."
                              class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold text-xs transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs active:scale-95">
                        Simpan Aset Baru
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL 2: EDIT ASET -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.away="editModal = false"
             class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-5 sm:p-7 shadow-2xl relative animate-in fade-in zoom-in-95 duration-150">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Data Aset Konveksi</h3>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="editForm.code ? 'Kode: ' + editForm.code : 'Perbarui valuasi aset'"></p>
                </div>
                <button type="button" @click="editModal = false" class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form method="POST" :action="`{{ url('admin/assets') }}/${editForm.id}`" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <!-- Nama Aset -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Aset / Mesin / Alat <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editForm.name" required
                           class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                </div>

                <!-- Kategori & Kondisi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori Aset <span class="text-rose-500">*</span></label>
                        <select name="category" x-model="editForm.category" required class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                            @foreach($categories as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kondisi Saat Ini <span class="text-rose-500">*</span></label>
                        <select name="condition" x-model="editForm.condition" required class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                            @foreach($conditions as $cKey => $cLabel)
                                <option value="{{ $cKey }}">{{ $cLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Qty, Satuan & Tgl Pembelian -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jumlah (Qty) <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" min="1" x-model="editForm.quantity" required
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Satuan</label>
                        <input type="text" name="unit" x-model="editForm.unit"
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tgl Pembelian</label>
                        <input type="date" name="purchase_date" x-model="editForm.purchase_date"
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>
                </div>

                <!-- Harga Beli & Estimasi Valuasi Saat Ini -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Harga Beli / Perolehan (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="purchase_price" min="0" x-model="editForm.purchase_price" required
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Estimasi Valuasi Saat Ini (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="current_value" min="0" x-model="editForm.current_value" required
                               class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                    </div>
                </div>

                <!-- Lokasi Workshop -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lokasi / Ruang Workshop</label>
                    <input type="text" name="location" x-model="editForm.location"
                           class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none">
                </div>

                <!-- Catatan -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan / Spesifikasi</label>
                    <textarea name="notes" rows="2" x-model="editForm.notes"
                              class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold text-xs transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition-colors shadow-xs active:scale-95">
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

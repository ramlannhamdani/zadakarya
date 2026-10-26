@extends('layouts.admin')

@section('title', 'Katalog Layanan Konveksi')

@section('header-actions')
<a href="{{ route('admin.services.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Tambah Layanan</span>
</a>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="bg-white rounded-2xl p-6 shadow-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
            <!-- Total Layanan -->
            <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Layanan</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</span>
                    <span class="text-[11px] text-slate-400">segmen konveksi</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Produk aktif & penawaran</p>
            </div>

            <!-- Layanan Aktif (Live di Web) -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Aktif (Live di Web)</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($stats['active']) }}</span>
                    <span class="text-[11px] text-emerald-600/80 font-medium">tayang di website</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Dapat dipesan pelanggan</p>
            </div>

            <!-- Nonaktif / Draft -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Nonaktif (Draft)</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-slate-600">{{ number_format($stats['inactive']) }}</span>
                    <span class="text-[11px] text-slate-400 font-medium">disembunyikan</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Pengembangan produk baru</p>
            </div>

            <!-- Standar Min. Order -->
            <div class="pt-4 sm:pt-0 sm:px-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Standar Min. Order</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl sm:text-3xl font-bold text-brand-700">{{ $stats['avg_min_order'] }}</span>
                    <span class="text-[11px] text-slate-400">Pcs / kloter</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Kapasitas potong & jahit</p>
            </div>
        </div>
    </section>

    <!-- 2. TOOLBAR FILTER & SEARCH -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        <form method="GET" action="{{ route('admin.services.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Status Tab Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
                <a href="{{ route('admin.services.index', request()->except('status')) }}" class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('status') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua Layanan ({{ $stats['total'] }})
                </a>
                <a href="{{ route('admin.services.index', array_merge(request()->query(), ['status' => 'active'])) }}" class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'active' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Aktif ({{ $stats['active'] }})
                </a>
                <a href="{{ route('admin.services.index', array_merge(request()->query(), ['status' => 'inactive'])) }}" class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'inactive' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Nonaktif ({{ $stats['inactive'] }})
                </a>
            </div>

            <!-- Search & Quick Action -->
            <div class="flex items-center gap-2.5">
                <div class="relative w-full sm:w-64">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama layanan / slug..." class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                </div>
            </div>

        </form>
    </section>

    <!-- 3. DATA TABLE LAYANAN -->
    <section class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">LAYANAN</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">SLUG (URL)</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">MIN. ORDER</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">URUTAN</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">STATUS</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($services as $service)
                        @php
                            $isActive = $service->is_published;
                            $icon = 'fa-shirt';
                            $sName = strtolower($service->name);
                            if (str_contains($sName, 'jas') || str_contains($sName, 'almet')) $icon = 'fa-user-tie';
                            elseif (str_contains($sName, 'kemeja') || str_contains($sName, 'pdh')) $icon = 'fa-clipboard-user';
                            elseif (str_contains($sName, 'jaket') || str_contains($sName, 'hoodie')) $icon = 'fa-vest-patches';
                            elseif (str_contains($sName, 'polo')) $icon = 'fa-vest';
                            elseif (str_contains($sName, 'jersey')) $icon = 'fa-volleyball';
                            elseif (str_contains($sName, 'rompi')) $icon = 'fa-shield';
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- 1. LAYANAN -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    @if($service->featured_image)
                                        <img src="{{ asset('storage/'.$service->featured_image) }}" alt="{{ $service->name }}" class="w-8 h-8 rounded-xl object-cover bg-slate-100 shrink-0 border border-slate-100">
                                    @else
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs shrink-0">
                                            <i class="fa-solid {{ $icon }}"></i>
                                        </div>
                                    @endif
                                    <div class="space-y-0.5">
                                        <a href="{{ route('admin.services.edit', $service) }}" class="font-bold text-slate-900 block hover:text-brand-700 transition-colors">
                                            {{ $service->name }}
                                        </a>
                                        @if($service->short_description || $service->description)
                                            <span class="text-[11px] text-slate-400 max-w-xs truncate block" title="{{ strip_tags($service->short_description ?: $service->description) }}">
                                                {{ Str::limit(strip_tags($service->short_description ?: $service->description), 50) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 2. SLUG (URL) -->
                            <td class="py-3.5 px-5 font-mono text-[11px] text-brand-700 font-medium">
                                <span class="text-slate-400">/layanan/</span>{{ $service->slug }}
                            </td>

                            <!-- 3. MIN. ORDER -->
                            <td class="py-3.5 px-5 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 font-mono">
                                    {{ $service->min_order ?? '—' }} Pcs
                                </span>
                            </td>

                            <!-- 4. URUTAN -->
                            <td class="py-3.5 px-5 text-center font-mono font-bold text-slate-800">
                                {{ $service->sort_order }}
                            </td>

                            <!-- 5. STATUS -->
                            <td class="py-3.5 px-5 text-center">
                                @if($isActive)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-medium text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- 6. AKSI -->
                            <td class="py-3.5 px-5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('services.show', $service) }}" target="_blank" title="Lihat di Web" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors">
                                        <i class="fa-regular fa-eye text-xs"></i>
                                    </a>

                                    <a href="{{ route('admin.services.edit', $service) }}" title="Edit Layanan" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-700 hover:bg-brand-50 transition-colors">
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                    </a>

                                    <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('Hapus layanan {{ $service->name }}?')" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus Layanan" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-shirt text-2xl mb-2 block text-slate-300"></i>
                                Belum ada layanan konveksi yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="p-4 sm:px-6 border-t border-slate-50 bg-white">
                {{ $services->links() }}
            </div>
        @endif
    </section>

</div>
@endsection

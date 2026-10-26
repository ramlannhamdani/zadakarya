@extends('layouts.admin')

@section('title', 'Manajemen Logo Klien & Mitra')

@section('header-actions')
<a href="{{ route('admin.clients.create') }}" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
    <i class="fa-solid fa-plus text-[10px]"></i>
    <span>Tambah Klien</span>
</a>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. SUMMARY METRICS TILES -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total Mitra -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Klien</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</span>
                <span class="text-[11px] text-slate-400">mitra logo</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Brand & instansi rekanan</p>
        </div>

        <!-- Tayang di Website -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tayang di Website</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-emerald-600">{{ number_format($stats['live']) }}</span>
                <span class="text-[11px] text-emerald-600/80 font-medium truncate">muncul di publik</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Logo slider website</p>
        </div>

        <!-- Disembunyikan / Draft -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Disembunyikan</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-600">{{ number_format($stats['hidden']) }}</span>
                <span class="text-[11px] text-slate-400 font-medium truncate">arsip/pending</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Tidak tampil di web</p>
        </div>

        <!-- Klien dengan Website URL -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100/60">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Dengan Tautan URL</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-brand-700">{{ number_format($stats['linked']) }}</span>
                <span class="text-[11px] text-slate-400 font-medium truncate">link aktif</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Bisa diklik menuju web</p>
        </div>
    </section>

    <!-- 2. TOOLBAR FILTER & SEARCH -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Status Tab Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs no-scrollbar">
                <a href="{{ route('admin.clients.index', request()->except('status')) }}" class="px-3.5 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('status') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua Klien ({{ $stats['total'] }})
                </a>
                <a href="{{ route('admin.clients.index', array_merge(request()->query(), ['status' => 'live'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'live' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Tayang di Web ({{ $stats['live'] }})
                </a>
                <a href="{{ route('admin.clients.index', array_merge(request()->query(), ['status' => 'hidden'])) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap {{ request('status') === 'hidden' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Disembunyikan ({{ $stats['hidden'] }})
                </a>
            </div>

            <!-- Search & View Mode Switcher -->
            <form method="GET" action="{{ route('admin.clients.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 sm:w-64 sm:flex-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama klien / website..." class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 border border-slate-200/60 focus:outline-none focus:ring-1 focus:ring-slate-300">
                </div>

                <!-- View Switcher -->
                <div class="flex items-center bg-slate-100 p-0.5 rounded-xl shrink-0">
                    <button type="button" onclick="switchClientView('grid')" id="btnClientGrid" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-xs transition-all" title="Tampilan Kotak Logo">
                        <i class="fa-solid fa-table-cells-large"></i>
                    </button>
                    <button type="button" onclick="switchClientView('table')" id="btnClientTable" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-800 transition-all" title="Tampilan Tabel Data">
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
            </form>

        </div>
    </section>

    <!-- 3A. LOGO CARD GRID VIEW -->
    <section id="clientGridView" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @forelse($clients as $client)
            @php
                $isLive = $client->is_active;
            @endphp
            <div class="group relative bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between border border-transparent hover:border-slate-100">
                <!-- Top Status Indicator -->
                <div class="flex items-center justify-between mb-2">
                    @if($isLive)
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Tayang
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-medium">
                            <i class="fa-solid fa-eye-slash text-[9px]"></i> Disembunyikan
                        </span>
                    @endif

                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.clients.edit', $client) }}" title="Edit Klien" class="w-6 h-6 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-50 flex items-center justify-center transition-colors">
                            <i class="fa-regular fa-pen-to-square text-[10px]"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" onsubmit="return confirm('Hapus klien {{ $client->name }}?')" class="inline-block">
                            @csrf @method('DELETE')
                            <button type="submit" title="Hapus Klien" class="w-6 h-6 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors">
                                <i class="fa-regular fa-trash-can text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Logo Container -->
                <div class="h-24 w-full rounded-xl bg-slate-50 flex items-center justify-center p-3 my-1">
                    @if($client->logo_path)
                        <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" class="max-h-full max-w-full object-contain filter group-hover:scale-105 transition-transform duration-200">
                    @else
                        <span class="text-sm font-bold text-slate-400">{{ mb_substr($client->name, 0, 2) }}</span>
                    @endif
                </div>

                <!-- Bottom Info: Nama & Link Website -->
                <div class="pt-3 border-t border-slate-50 space-y-1">
                    <span class="font-bold text-slate-900 block truncate text-xs" title="{{ $client->name }}">{{ $client->name }}</span>
                    @if($client->website_url)
                        <a href="{{ $client->website_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[11px] font-mono text-brand-700 hover:underline truncate max-w-full">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                            <span class="truncate">{{ preg_replace('#^https?://#', '', $client->website_url) }}</span>
                        </a>
                    @else
                        <span class="text-[10px] text-slate-400 italic block">Tanpa website</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-2xl shadow-sm p-8">
                <i class="fa-regular fa-handshake text-3xl mb-2 block text-slate-300"></i>
                Belum ada logo klien yang cocok dengan pencarian atau filter.
            </div>
        @endforelse
    </section>

    <!-- 3B. TABLE VIEW -->
    <section id="clientTableView" class="bg-white rounded-2xl shadow-sm overflow-hidden hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">LOGO</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">NAMA KLIEN</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider">WEBSITE URL</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">STATUS WEB</th>
                        <th class="py-3.5 px-5 font-normal uppercase tracking-wider text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-5 text-center">
                                <div class="w-12 h-8 mx-auto bg-slate-50 rounded-lg flex items-center justify-center p-1 border border-slate-100">
                                    @if($client->logo_path)
                                        <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" class="max-h-full max-w-full object-contain">
                                    @else
                                        <span class="text-[10px] font-bold text-slate-400">{{ mb_substr($client->name, 0, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-5 font-bold text-slate-900">
                                <a href="{{ route('admin.clients.edit', $client) }}" class="hover:text-brand-700 transition-colors">
                                    {{ $client->name }}
                                </a>
                            </td>
                            <td class="py-3.5 px-5 font-mono text-[11px] text-brand-700">
                                @if($client->website_url)
                                    <a href="{{ $client->website_url }}" target="_blank" rel="noopener" class="hover:underline">
                                        {{ $client->website_url }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-sans italic text-[10px]">Tanpa website</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                @if($client->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Tayang
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-medium text-[10px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Disembunyikan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.clients.edit', $client) }}" title="Edit Klien" class="p-1.5 rounded-lg text-slate-400 hover:text-brand-700 hover:bg-brand-50 transition-colors">
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" onsubmit="return confirm('Hapus klien {{ $client->name }}?')" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus Klien" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400">Belum ada klien mitra.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>

<script>
function switchClientView(mode) {
    const grid = document.getElementById('clientGridView');
    const table = document.getElementById('clientTableView');
    const btnGrid = document.getElementById('btnClientGrid');
    const btnTable = document.getElementById('btnClientTable');

    if (mode === 'grid') {
        grid.classList.remove('hidden');
        table.classList.add('hidden');
        btnGrid.className = 'px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-xs transition-all';
        btnTable.className = 'px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-800 transition-all';
    } else {
        grid.classList.add('hidden');
        table.classList.remove('hidden');
        btnTable.className = 'px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-xs transition-all';
        btnGrid.className = 'px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-800 transition-all';
    }
}
</script>
@endsection

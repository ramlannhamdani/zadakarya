@extends('layouts.admin')

@section('title', 'Konsultasi & Inquiry Prospek')

@section('content')
<!-- 1. SUMMARY METRICS TILES (Sleek Flat Container) -->
<section class="bg-white rounded-2xl p-6 shadow-sm">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <!-- Total Masuk -->
        <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Konsultasi</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-slate-900">{{ $stats['total'] }}</span>
                <span class="text-[11px] text-slate-400">lead</span>
            </div>
        </div>

        <!-- Perlu Dihubungi -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Perlu Follow Up</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-amber-600">{{ $stats['new'] }}</span>
                <span class="text-[11px] text-amber-600/80 font-medium">belum direspons</span>
            </div>
        </div>

        <!-- Dalam Diskusi / Penawaran -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Tahap Diskusi</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-blue-600">{{ $stats['contacted'] }}</span>
                <span class="text-[11px] text-blue-600/80 font-medium">nego harga/bahan</span>
            </div>
        </div>

        <!-- Konversi Jadi Pesanan -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Closing Pesanan</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-bold text-emerald-600">{{ $stats['deal'] }}</span>
                <span class="text-[11px] text-emerald-600/80 font-medium">
                    jadi order ({{ $stats['total'] > 0 ? round(($stats['deal'] / $stats['total']) * 100) : 0 }}%)
                </span>
            </div>
        </div>
    </div>
</section>

<!-- 2. FILTER & ACTION TOOLBAR -->
<section class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
            <a href="{{ route('admin.inquiries.index') }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ !request('status') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Semua Konsultasi ({{ $stats['total'] }})
            </a>
            <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ request('status') === 'new' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Baru Masuk ({{ $stats['new'] }})
            </a>
            <a href="{{ route('admin.inquiries.index', ['status' => 'contacted']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ request('status') === 'contacted' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Sudah Dihubungi ({{ $stats['contacted'] }})
            </a>
            <a href="{{ route('admin.inquiries.index', ['status' => 'deal']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ request('status') === 'deal' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Deal / Jadi Order ({{ $stats['deal'] }})
            </a>
            <a href="{{ route('admin.inquiries.index', ['status' => 'lost']) }}"
               class="px-3 py-1.5 rounded-xl font-semibold transition-all whitespace-nowrap {{ request('status') === 'lost' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                Batal / Lost
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('admin.inquiries.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Nama / Layanan / No WA..."
                       class="w-full bg-slate-50 text-xs rounded-xl pl-9 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-300">
            </div>

            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Cari
            </button>
            @if(request()->hasAny(['q', 'status']))
            <a href="{{ route('admin.inquiries.index') }}" class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs transition-colors">
                Reset
            </a>
            @endif
        </form>

    </div>
</section>

<!-- 3. DATA TABLE KONSULTASI -->
<section class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs whitespace-nowrap">
            <thead>
                <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                    <th class="py-3.5 px-5 font-normal">NAMA</th>
                    <th class="py-3.5 px-5 font-normal">LAYANAN</th>
                    <th class="py-3.5 px-5 font-normal text-center">ESTIMASI QTY</th>
                    <th class="py-3.5 px-5 font-normal">WHATSAPP</th>
                    <th class="py-3.5 px-5 font-normal text-center">TANGGAL</th>
                    <th class="py-3.5 px-5 font-normal text-center">STATUS</th>
                    <th class="py-3.5 px-5 font-normal text-center">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($inquiries as $inquiry)
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="py-3.5 px-5">
                        <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="font-bold text-slate-900 block hover:text-brand-600 transition-colors">
                            {{ $inquiry->name }}
                        </a>
                        @if($inquiry->company)
                            <span class="text-[11px] text-slate-400">{{ $inquiry->company }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5">
                        <span class="text-slate-600">{{ $inquiry->service_name ?: 'Umum / Konveksi' }}</span>
                    </td>
                    <td class="py-3.5 px-5 text-center font-semibold text-slate-700">
                        {{ $inquiry->estimated_quantity ? $inquiry->estimated_quantity . ' pcs' : '—' }}
                    </td>
                    <td class="py-3.5 px-5">
                        @if($inquiry->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $inquiry->whatsapp) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 text-slate-700 hover:text-emerald-600 transition-colors font-mono">
                                <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                <span>{{ $inquiry->whatsapp }}</span>
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-center text-slate-500">
                        {{ $inquiry->created_at->format('d/m/Y') }}
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        @if($inquiry->status === 'new')
                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-700">
                                Baru Masuk
                            </span>
                        @elseif($inquiry->status === 'contacted')
                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700">
                                Sudah Dihubungi
                            </span>
                        @elseif($inquiry->status === 'deal')
                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                                Deal / Order
                            </span>
                        @else
                            <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-600">
                                {{ $inquiry->status_label }}
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.inquiries.show', $inquiry) }}" title="Lihat Detail & Tindak Lanjuti"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                <i class="fa-regular fa-eye text-xs"></i>
                            </a>
                            @if($inquiry->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $inquiry->whatsapp) }}?text={{ urlencode('Halo Kak ' . $inquiry->name . ', saya dari Zada Karya Production ingin menindaklanjuti konsultasi Anda mengenai ' . ($inquiry->service_name ?: 'pakaian custom')) }}"
                               target="_blank" title="Chat WhatsApp"
                               class="p-1.5 rounded-lg text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors">
                                <i class="fa-brands fa-whatsapp text-xs"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                        Tidak ada data konsultasi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Pagination -->
    @if($inquiries->hasPages() || $inquiries->total() > 0)
    <div class="p-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-50">
        <span>Menampilkan {{ $inquiries->count() }} dari {{ $inquiries->total() }} konsultasi</span>
        <div>
            {{ $inquiries->links() }}
        </div>
    </div>
    @endif
</section>
@endsection

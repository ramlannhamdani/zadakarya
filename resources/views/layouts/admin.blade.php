<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Zada Karya Production</title>
    @if(setting('favicon'))
        <link rel="icon" href="{{ asset('storage/'.setting('favicon')) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('images/brand/emblem.png') }}">
    @endif

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js for visualization if needed -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Custom scrollbar halus */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-[#F8FAFC] text-slate-800 font-sans antialiased" x-data="{ sidebar: false }">

<div class="flex min-h-screen">
    
    <!-- SIDEBAR (Flat, Tanpa Stroke Tebal) -->
    <aside id="sidebar" class="w-64 bg-white flex flex-col justify-between shrink-0 fixed inset-y-0 left-0 z-40 transition-transform duration-300 lg:static -translate-x-full lg:translate-x-0 shadow-[1px_0_0_0_#f1f5f9]"
           :class="sidebar ? '!translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="min-h-0 flex-1 flex flex-col">
            <!-- Brand Header -->
            <div class="h-20 flex items-center justify-between px-6 shrink-0 border-b border-slate-50 lg:border-b-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3.5 group">
                    @if(setting('logo'))
                        <img src="{{ asset('storage/'.setting('logo')) }}" alt="Zada Karya" class="h-9 w-auto max-w-[130px] object-contain">
                    @elseif(setting('favicon'))
                        <div class="w-9 h-9 rounded-xl bg-brand-600 flex items-center justify-center p-1.5 shadow-xs shrink-0">
                            <img src="{{ asset('storage/'.setting('favicon')) }}" alt="Zada Karya" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-9 h-9 rounded-xl bg-brand-600 flex items-center justify-center p-1.5 shadow-xs shrink-0 transition-transform group-hover:scale-105">
                            <img src="{{ asset('images/brand/emblem-white.png') }}" alt="Zada Karya" class="w-full h-full object-contain">
                        </div>
                    @endif
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 leading-snug group-hover:text-brand-700 transition-colors">Zada Karya</h1>
                        <span class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">Admin Workspace</span>
                    </div>
                </a>
                <button type="button" @click="sidebar = false" class="lg:hidden text-slate-400 hover:text-slate-600 p-1.5 rounded-lg -mr-1" aria-label="Tutup Menu">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Navigation Menu -->
            @php
                $mainNav = [
                    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'fa-solid fa-layer-group'],
                    ['label' => 'Pesanan', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'fa-solid fa-box-open'],
                    ['label' => 'Customer', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'icon' => 'fa-solid fa-users'],
                    ['label' => 'Konsultasi', 'route' => 'admin.inquiries.index', 'match' => 'admin.inquiries.*', 'icon' => 'fa-regular fa-comment-dots'],
                    ['label' => 'Invoice', 'route' => 'admin.invoices.index', 'match' => 'admin.invoices.*', 'icon' => 'fa-solid fa-receipt'],
                    ['label' => 'Pembayaran', 'route' => 'admin.payments.index', 'match' => 'admin.payments.*', 'icon' => 'fa-regular fa-credit-card'],
                    ['label' => 'Beban Operasional', 'route' => 'admin.expenses.index', 'match' => 'admin.expenses.*', 'icon' => 'fa-solid fa-arrow-right-arrow-left'],
                    ['label' => 'Pinjaman', 'route' => 'admin.loans.index', 'match' => 'admin.loans.*', 'icon' => 'fa-solid fa-hand-holding-dollar'],
                    ['label' => 'Aset Konveksi', 'route' => 'admin.assets.index', 'match' => 'admin.assets.*', 'icon' => 'fa-solid fa-boxes-stacked'],
                    ['label' => 'Neraca & Laba Rugi', 'route' => 'admin.finance.index', 'match' => 'admin.finance.*', 'icon' => 'fa-solid fa-scale-balanced'],
                    ['label' => 'Spreadsheet', 'route' => 'admin.spreadsheet.index', 'match' => 'admin.spreadsheet.*', 'icon' => 'fa-regular fa-file-lines'],
                ];

                $contentNav = [
                    ['label' => 'Portfolio', 'route' => 'admin.portfolio.index', 'match' => 'admin.portfolio.*', 'icon' => 'fa-regular fa-images'],
                    ['label' => 'Galeri', 'route' => 'admin.gallery.index', 'match' => 'admin.gallery.*', 'icon' => 'fa-solid fa-camera-retro'],
                    ['label' => 'Layanan', 'route' => 'admin.services.index', 'match' => 'admin.services.*', 'icon' => 'fa-solid fa-shirt'],
                    ['label' => 'Ulasan', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'icon' => 'fa-regular fa-star'],
                    ['label' => 'Klien', 'route' => 'admin.clients.index', 'match' => 'admin.clients.*', 'icon' => 'fa-regular fa-handshake'],
                    ['label' => 'Blog', 'route' => 'admin.articles.index', 'match' => 'admin.articles.*', 'icon' => 'fa-regular fa-newspaper'],
                    ['label' => 'Pengaturan', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'fa-solid fa-sliders'],
                ];
            @endphp

            <nav class="px-3 py-2 space-y-0.5 overflow-y-auto flex-1">
                @foreach($mainNav as $item)
                    @php $isActive = request()->routeIs($item['match']); @endphp
                    <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-xs transition-all {{ $isActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        <i class="{{ $item['icon'] }} w-4 text-center {{ $isActive ? 'text-slate-300' : 'text-slate-400' }}"></i>
                        <span>{{ $item['label'] }}</span>
                        @if($isActive)
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        @endif
                    </a>
                @endforeach

                <div class="pt-4 pb-1.5 px-3.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Website Content</span>
                </div>

                @foreach($contentNav as $item)
                    @php $isActive = request()->routeIs($item['match']); @endphp
                    <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-xs transition-all {{ $isActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        <i class="{{ $item['icon'] }} w-4 text-center {{ $isActive ? 'text-slate-300' : 'text-slate-400' }}"></i>
                        <span>{{ $item['label'] }}</span>
                        @if($isActive)
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Bottom sidebar item -->
        <div class="p-3 shrink-0">
            <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-50 font-medium text-xs transition-colors">
                <i class="fa-solid fa-arrow-up-right-from-square text-slate-400"></i>
                <span>Lihat Website</span>
            </a>
        </div>
    </aside>

    <!-- Mobile/Tablet Sidebar Overlay Backdrop -->
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs z-30 lg:hidden"></div>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- TOP NAVBAR (Clean & Borderless) -->
        <header class="h-16 bg-white/70 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <button type="button" @click="sidebar = !sidebar" class="lg:hidden text-slate-500 p-2 hover:bg-slate-100 rounded-lg shrink-0" aria-label="Buka Menu">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
                <div class="min-w-0">
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight truncate max-w-[130px] sm:max-w-xs md:max-w-none">@yield('title', 'Dashboard')</h2>
                </div>
            </div>

            <!-- User Controls & Custom Header Actions -->
            <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                @yield('header-actions')

                <button title="Notifikasi" class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors relative">
                    <i class="fa-regular fa-bell text-xs"></i>
                    <span class="absolute top-2 right-2 w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                </button>

                @php
                    $userName = auth()->user()->name ?? 'Admin';
                    $words = explode(' ', trim($userName));
                    $initials = '';
                    foreach (array_slice($words, 0, 2) as $w) {
                        $initials .= strtoupper(substr($w, 0, 1));
                    }
                    if (!$initials) $initials = 'AZ';
                @endphp

                <div class="flex items-center gap-2 sm:gap-2.5 pl-1 border-l border-slate-100">
                    <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-700 border border-brand-100/80 font-bold flex items-center justify-center text-xs shrink-0 overflow-hidden shadow-2xs">
                        <img src="{{ asset('images/brand/emblem.png') }}" alt="{{ $userName }}" class="w-5 h-5 object-contain">
                    </div>
                    <div class="text-left hidden sm:block">
                        <span class="text-xs font-semibold text-slate-800 block leading-tight">{{ $userName }}</span>
                        <span class="text-[10px] text-slate-400">Super Administrator</span>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" class="inline-block ml-0.5 sm:ml-1">
                        @csrf
                        <button type="submit" title="Logout" class="text-slate-400 hover:text-slate-600 p-1.5 rounded transition-colors">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- PAGE BODY CONTAINER -->
        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full flex-1">
            @if(session('success'))
                <div class="flex items-center justify-between gap-3 rounded-2xl bg-emerald-50/90 border border-emerald-100 px-4 py-3 text-xs font-medium text-emerald-800 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center justify-between gap-3 rounded-2xl bg-rose-50/90 border border-rose-100 px-4 py-3 text-xs font-medium text-rose-800 shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
            @endif
            @if($errors->any() && !request()->routeIs('admin.login'))
                <div class="rounded-2xl bg-rose-50/90 border border-rose-100 px-4 py-3 text-xs text-rose-800 shadow-xs">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                        <span>Terjadi kesalahan validasi:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5 text-rose-700">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>

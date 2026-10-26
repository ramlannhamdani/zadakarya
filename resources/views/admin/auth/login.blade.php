<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Admin — Zada Karya Production</title>
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#F8FAFC] px-4 font-sans antialiased text-slate-800">
    <div class="w-full max-w-sm">
        <div class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-lg font-extrabold text-white shadow-sm">
                ZK
            </div>
            <h1 class="mt-4 text-xl font-bold tracking-tight text-slate-900">Zada Karya Production</h1>
            <p class="mt-1 text-xs text-slate-400">Panel Manajemen Konveksi &amp; Produksi</p>
        </div>

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-8 rounded-2xl border border-slate-100 bg-white p-7 sm:p-8 shadow-sm space-y-4">
            @csrf

            @if($errors->any())
                <div class="rounded-xl border border-rose-100 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-700 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="email">Alamat Email</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 pl-9 pr-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors"
                           type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@zadakarya.com">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="password">Kata Sandi</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 pl-9 pr-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none transition-colors"
                           type="password" id="password" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <button type="submit" class="w-full mt-2 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all flex items-center justify-center gap-2">
                <span>Masuk ke Panel Admin</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </button>
        </form>
    </div>
</body>
</html>

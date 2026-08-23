<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Admin — Zada Karya Production</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-cream px-4">
    <div class="w-full max-w-sm">
        <div class="text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-lg font-extrabold text-white">ZK</span>
            <h1 class="mt-4 text-xl font-extrabold text-ink">Zada Karya Production</h1>
            <p class="mt-1 text-sm text-neutral-500">Masuk ke panel admin</p>
        </div>

        <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-8 rounded-xl border border-line bg-white p-6">
            @csrf

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label class="form-label" for="email">Email</label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="mt-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password" required>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-neutral-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-line text-brand-600 focus:ring-brand-600">
                Ingat saya
            </label>
            <button type="submit" class="btn-primary mt-5 w-full">Masuk</button>
        </form>
    </div>
</body>
</html>

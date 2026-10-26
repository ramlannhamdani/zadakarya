@extends('layouts.admin')

@section('title', $client->exists ? 'Edit Klien' : 'Tambah Klien')

@section('content')
<div class="max-w-2xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Klien</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
        <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid fa-handshake"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $client->exists ? 'Edit Data Klien & Mitra' : 'Tambah Klien & Mitra Baru' }}</h3>
                <p class="text-xs text-slate-400">Logo perusahaan, instansi, atau komunitas yang pernah bekerja sama dengan Zada Karya</p>
            </div>
        </div>

        <form method="POST" action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if($client->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                        Nama Klien / Perusahaan / Instansi <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required placeholder="Contoh: PT Kereta Api Indonesia (Persero)">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Logo Klien @unless($client->exists)<span class="text-rose-500">*</span>@endunless
                    </label>
                    @if($client->logo_path)
                        <div class="mb-3 inline-flex h-20 items-center rounded-xl bg-slate-50 border border-slate-200/80 px-6 py-2">
                            <img src="{{ asset('storage/'.$client->logo_path) }}" alt="{{ $client->name }}" class="max-h-14 w-auto object-contain">
                        </div>
                    @endif
                    <x-admin.media-picker name="logo" />
                    <p class="mt-1.5 text-[11px] text-slate-400">Format PNG/WebP berlatar transparan, lebar minimal 300px. Tampil rapi pada logo marquee website.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="website_url">
                        Alamat Website (Opsional)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                           type="url" id="website_url" name="website_url" value="{{ old('website_url', $client->website_url) }}" placeholder="https://perusahaan.co.id">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="sort_order">
                        Urutan Tampil (Sort Order)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none font-mono"
                           type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $client->sort_order ?? 0) }}">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-800 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active ?? true)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-600">
                    <span>Tampilkan logo ini pada carousel mitra di website</span>
                </label>
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.clients.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>{{ $client->exists ? 'Simpan Perubahan' : 'Tambah Klien' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

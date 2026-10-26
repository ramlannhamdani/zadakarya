@extends('layouts.admin')

@section('title', $customer->exists ? 'Edit Customer' : 'Customer Baru')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Pelanggan</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100">
        <div class="flex items-center gap-3 pb-6 mb-6 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid {{ $customer->exists ? 'fa-user-pen' : 'fa-user-plus' }}"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $customer->exists ? 'Edit Data Pelanggan' : 'Tambah Pelanggan Baru' }}</h3>
                <p class="text-xs text-slate-400">Lengkapi data kontak dan alamat untuk kemudahan invoicing dan pengiriman pesanan</p>
            </div>
        </div>

        <form method="POST" action="{{ $customer->exists ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" class="space-y-5">
            @csrf
            @if($customer->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required placeholder="Contoh: Budi Santoso">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="company">
                        Perusahaan / Instansi (Opsional)
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="text" id="company" name="company" value="{{ old('company', $customer->company) }}" placeholder="Contoh: PT Sumber Berkah / Komunitas Vespa">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="whatsapp">
                        Nomor WhatsApp
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}" placeholder="+62 8xx-xxxx-xxxx">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="email">
                        Alamat Email
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" placeholder="email@contoh.com">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="address">
                        Alamat Pengiriman / Kantor
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                              id="address" name="address" rows="2" placeholder="Nama jalan, nomor gedung, RT/RW, kelurahan, kecamatan...">{{ old('address', $customer->address) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="city">
                        Kota / Kabupaten
                    </label>
                    <input class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                           type="text" id="city" name="city" value="{{ old('city', $customer->city) }}" placeholder="Contoh: Bandung">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="notes">
                        Catatan Khusus Pelanggan
                    </label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:ring-1 focus:ring-brand-600/20 focus:outline-none transition-colors"
                              id="notes" name="notes" rows="3" placeholder="Preferensi kain, spesifikasi langganan, instruksi invoice, dll...">{{ old('notes', $customer->notes) }}</textarea>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.customers.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>{{ $customer->exists ? 'Simpan Perubahan' : 'Buat Customer' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

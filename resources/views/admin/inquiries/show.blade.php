@extends('layouts.admin')

@section('title', 'Inquiry #'.$inquiry->id)

@section('content')
<div class="space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.inquiries.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Inquiry Masuk</span>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- LEFT: DETAIL KONSULTASI -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-100 lg:col-span-2 space-y-6">
            <div class="flex items-center gap-3 pb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Detail Pesan Konsultasi Masuk #{{ $inquiry->id }}</h3>
                    <p class="text-xs text-slate-400">Pesan dari formulir konsultasi website yang dikirimkan oleh calon pelanggan</p>
                </div>
            </div>

            <dl class="grid gap-5 text-xs sm:grid-cols-2">
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nama Calon Pelanggan</dt>
                    <dd class="mt-1 font-bold text-slate-900 text-sm">{{ $inquiry->name }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Perusahaan / Instansi</dt>
                    <dd class="mt-1 font-medium text-slate-700">{{ $inquiry->company ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nomor WhatsApp</dt>
                    <dd class="mt-1 flex items-center gap-2">
                        <span class="font-bold font-mono text-slate-800">{{ $inquiry->whatsapp }}</span>
                        @if($inquiry->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $inquiry->whatsapp) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold text-[11px] transition-colors">
                                <i class="fa-brands fa-whatsapp text-xs"></i>
                                <span>Hubungi</span>
                            </a>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Alamat Email</dt>
                    <dd class="mt-1 font-medium text-slate-700">{{ $inquiry->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kategori Layanan</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $inquiry->service_name ?: 'Konsultasi Umum' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Estimasi Kuantiti</dt>
                    <dd class="mt-1 font-bold font-mono text-slate-800">{{ $inquiry->estimated_quantity ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Target Tanggal Selesai</dt>
                    <dd class="mt-1 font-medium text-slate-700">{{ $inquiry->target_date?->translatedFormat('d F Y') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Waktu Masuk</dt>
                    <dd class="mt-1 font-medium text-slate-500">{{ $inquiry->created_at->translatedFormat('d F Y H:i') }}</dd>
                </div>
            </dl>

            <div class="pt-4 border-t border-slate-100">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Deskripsi Kebutuhan</p>
                <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-4 text-xs text-slate-800 whitespace-pre-line leading-relaxed">
                    {{ $inquiry->description }}
                </div>
            </div>

            @if($inquiry->attachment_path)
                <div class="pt-2">
                    <a href="{{ route('admin.inquiries.attachment', $inquiry) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                        <i class="fa-solid fa-paperclip text-xs text-slate-400"></i>
                        <span>Unduh Lampiran Desain ({{ $inquiry->attachment_name }})</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- RIGHT: STATUS & KONVERSI -->
        <div class="space-y-6">
            <!-- STATUS & ADMIN NOTES -->
            <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
                @csrf @method('PATCH')
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Status &amp; Catatan Admin</h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">Status Progres</label>
                    <select class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 focus:bg-white focus:border-brand-600 focus:outline-none"
                            id="status" name="status">
                        @foreach(\App\Models\Inquiry::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($inquiry->status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="admin_notes">Catatan Tindak Lanjut</label>
                    <textarea class="w-full rounded-xl bg-slate-50 border border-slate-200/80 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:border-brand-600 focus:outline-none"
                              id="admin_notes" name="admin_notes" rows="4" placeholder="Catatan internal admin (sudah ditelepon, kirim penawaran, dll)...">{{ old('admin_notes', $inquiry->admin_notes) }}</textarea>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    Simpan Catatan
                </button>
            </form>

            <!-- KONVERSI JADI CUSTOMER -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-3">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Konversi ke Pelanggan</h3>
                @if($inquiry->customer_id)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3 text-xs text-emerald-800">
                        <p class="font-medium">Sudah terhubung ke data pelanggan:</p>
                        <a href="{{ route('admin.customers.show', $inquiry->customer_id) }}" class="mt-1 block font-bold text-brand-700 hover:underline">
                            {{ $inquiry->customer->name }} &rarr;
                        </a>
                    </div>
                @else
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Jika inquiry ini deal, klik tombol di bawah untuk langsung membuat data pelanggan baru dan menerbitkan order.
                    </p>
                    <form method="POST" action="{{ route('admin.inquiries.convert', $inquiry) }}">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                            <i class="fa-solid fa-user-plus text-[10px] mr-1.5"></i>
                            <span>Konversi Jadi Pelanggan</span>
                        </button>
                    </form>
                @endif
            </div>

            <!-- DELETE BUTTON -->
            <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}"
                  onsubmit="return confirm('Hapus inquiry ini secara permanen?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2.5 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-50 text-rose-600 text-xs font-semibold transition-colors">
                    Hapus Inquiry Ini
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

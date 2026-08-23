@extends('layouts.admin')

@section('title', 'Inquiry #'.$inquiry->id)

@section('content')
<div class="grid gap-5 lg:grid-cols-3">
    <div class="admin-card lg:col-span-2">
        <h2 class="font-extrabold text-ink">Detail Konsultasi</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Nama</dt><dd class="mt-0.5 font-medium">{{ $inquiry->name }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Perusahaan</dt><dd class="mt-0.5">{{ $inquiry->company ?? '—' }}</dd></div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">WhatsApp</dt>
                <dd class="mt-0.5 flex items-center gap-2">
                    {{ $inquiry->whatsapp }}
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $inquiry->whatsapp) }}" target="_blank" rel="noopener" class="rounded bg-[#25D366]/10 px-2 py-0.5 text-xs font-bold text-[#128C7E]">Chat</a>
                </dd>
            </div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Email</dt><dd class="mt-0.5">{{ $inquiry->email ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Layanan</dt><dd class="mt-0.5">{{ $inquiry->service_name ?: 'Umum' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Estimasi Jumlah</dt><dd class="mt-0.5">{{ $inquiry->estimated_quantity ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Target Selesai</dt><dd class="mt-0.5">{{ $inquiry->target_date?->translatedFormat('d F Y') ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Masuk Pada</dt><dd class="mt-0.5">{{ $inquiry->created_at->translatedFormat('d F Y H:i') }}</dd></div>
        </dl>
        <div class="mt-5">
            <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">Deskripsi Kebutuhan</p>
            <p class="mt-1.5 whitespace-pre-line rounded-lg bg-cream p-4 text-sm">{{ $inquiry->description }}</p>
        </div>
        @if($inquiry->attachment_path)
            <a href="{{ route('admin.inquiries.attachment', $inquiry) }}" class="btn-outline mt-4 !py-2 text-xs">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Unduh Lampiran ({{ $inquiry->attachment_name }})
            </a>
        @endif
    </div>

    <div class="space-y-5">
        <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}" class="admin-card">
            @csrf @method('PATCH')
            <h2 class="font-extrabold text-ink">Status &amp; Catatan</h2>
            <div class="mt-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-input" id="status" name="status">
                    @foreach(\App\Models\Inquiry::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($inquiry->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-4">
                <label class="form-label" for="admin_notes">Catatan Admin</label>
                <textarea class="form-input" id="admin_notes" name="admin_notes" rows="4">{{ old('admin_notes', $inquiry->admin_notes) }}</textarea>
            </div>
            <button type="submit" class="btn-primary mt-4 w-full">Simpan</button>
        </form>

        <div class="admin-card">
            <h2 class="font-extrabold text-ink">Konversi</h2>
            @if($inquiry->customer_id)
                <p class="mt-3 text-sm text-neutral-600">Sudah terhubung ke customer:</p>
                <a href="{{ route('admin.customers.show', $inquiry->customer_id) }}" class="mt-1 block font-semibold text-brand-600 hover:underline">{{ $inquiry->customer->name }}</a>
            @else
                <p class="mt-3 text-sm text-neutral-600">Deal dengan customer ini? Konversi menjadi data customer untuk membuat pesanan.</p>
                <form method="POST" action="{{ route('admin.inquiries.convert', $inquiry) }}">
                    @csrf
                    <button type="submit" class="btn-primary mt-4 w-full">Jadikan Customer</button>
                </form>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}"
              onsubmit="return confirm('Hapus inquiry ini secara permanen?')">
            @csrf @method('DELETE')
            <button type="submit" class="w-full rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">Hapus Inquiry</button>
        </form>
    </div>
</div>
@endsection

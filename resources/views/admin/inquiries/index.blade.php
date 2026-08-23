@extends('layouts.admin')

@section('title', 'Konsultasi / Inquiry')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1.5">
        <a href="{{ route('admin.inquiries.index') }}"
           class="rounded-full px-3.5 py-1.5 text-sm font-semibold {{ !request('status') ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600' }}">Semua</a>
        @foreach(\App\Models\Inquiry::STATUSES as $key => $label)
            <a href="{{ route('admin.inquiries.index', ['status' => $key]) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-semibold {{ request('status') === $key ? 'bg-brand-600 text-white' : 'border border-line bg-white text-neutral-600' }}">{{ $label }}</a>
        @endforeach
    </div>
    <form method="GET" class="flex gap-2">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari..." class="form-input !w-48">
        <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
    </form>
</div>

<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[760px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Nama</th>
                <th class="px-5 py-3">Layanan</th>
                <th class="px-5 py-3">Estimasi Qty</th>
                <th class="px-5 py-3">WhatsApp</th>
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($inquiries as $inquiry)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="font-semibold text-ink hover:text-brand-600">{{ $inquiry->name }}</a>
                        @if($inquiry->company)<span class="block text-xs text-neutral-500">{{ $inquiry->company }}</span>@endif
                    </td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $inquiry->service_name ?: 'Umum' }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $inquiry->estimated_quantity ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $inquiry->whatsapp }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $inquiry->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold
                            {{ $inquiry->status === 'new' ? 'bg-amber-100 text-amber-700' : ($inquiry->status === 'deal' ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-600') }}">
                            {{ $inquiry->status_label }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-500">Tidak ada inquiry.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $inquiries->links() }}</div>
@endsection

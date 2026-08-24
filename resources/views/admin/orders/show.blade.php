@extends('layouts.admin')

@section('title', 'Pesanan '.$order->order_number)

@section('content')
{{-- Header summary --}}
<div class="admin-card">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="font-mono text-2xl font-extrabold text-brand-600">{{ $order->order_number }}</span>
                <x-order-status-badge :status="$order->status" />
            </div>
            <p class="mt-1 font-semibold text-ink">{{ $order->name }}</p>
            <p class="text-sm text-neutral-500">
                <a href="{{ route('admin.customers.show', $order->customer) }}" class="font-medium text-brand-600 hover:underline">{{ $order->customer->display_name }}</a>
                &bull; dibuat {{ $order->created_at->translatedFormat('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('tracking.index', ['order' => $order->order_number]) }}" target="_blank" class="btn-outline !px-4 !py-2 text-xs">Lihat Tracking Publik</a>
            <a href="{{ route('admin.orders.edit', $order) }}" class="btn-outline !px-4 !py-2 text-xs">Edit Pesanan</a>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="flex items-center gap-1.5">
                @csrf @method('PATCH')
                <select name="status" class="form-input !w-auto !py-2 text-xs" onchange="this.form.submit()">
                    @foreach(\App\Models\Order::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-5 sm:grid-cols-5">
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Grand Total</dt>
            <dd class="mt-1 text-lg font-extrabold text-ink">{{ rupiah($order->grand_total) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Terbayar</dt>
            <dd class="mt-1 text-lg font-extrabold text-green-600">{{ rupiah($order->amount_paid) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Sisa</dt>
            <dd class="mt-1 text-lg font-extrabold {{ $order->remaining > 0 ? 'text-brand-600' : 'text-neutral-400' }}">{{ rupiah($order->remaining) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Pembayaran</dt>
            <dd class="mt-1.5"><x-payment-badge :status="$order->payment_status" /></dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Tahap Saat Ini</dt>
            <dd class="mt-1 text-sm font-bold text-ink">{{ $order->current_stage }}/7 — {{ $order->current_stage_name }}</dd>
        </div>
    </dl>
</div>

{{-- Tabs --}}
@php
    $tabs = [
        'overview' => 'Overview',
        'tracking' => 'Tracking',
        'photos' => 'Foto Produksi',
        'invoice' => 'Invoice',
        'payments' => 'Pembayaran',
        'files' => 'File',
        'history' => 'Riwayat',
        'notes' => 'Catatan Internal',
    ];
    $tab = array_key_exists($tab, $tabs) ? $tab : 'overview';
@endphp
<div class="mt-6 flex flex-wrap gap-1.5 border-b border-line pb-px">
    @foreach($tabs as $key => $label)
        <a href="{{ route('admin.orders.show', ['order' => $order, 'tab' => $key]) }}"
           class="rounded-t-lg px-4 py-2.5 text-sm font-semibold transition {{ $tab === $key ? 'border border-b-0 border-line bg-white text-brand-600' : 'text-neutral-500 hover:text-ink' }}">
            {{ $label }}
            @if($key === 'photos' && $order->productionPhotos->count())<span class="ml-1 rounded-full bg-neutral-100 px-1.5 text-xs">{{ $order->productionPhotos->count() }}</span>@endif
            @if($key === 'invoice' && $order->invoices->count())<span class="ml-1 rounded-full bg-neutral-100 px-1.5 text-xs">{{ $order->invoices->count() }}</span>@endif
            @if($key === 'payments' && $order->payments->count())<span class="ml-1 rounded-full bg-neutral-100 px-1.5 text-xs">{{ $order->payments->count() }}</span>@endif
            @if($key === 'files' && $order->attachments->count())<span class="ml-1 rounded-full bg-neutral-100 px-1.5 text-xs">{{ $order->attachments->count() }}</span>@endif
        </a>
    @endforeach
</div>

<div class="mt-5">
    {{-- ============ OVERVIEW ============ --}}
    @if($tab === 'overview')
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Item Produk</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                                <th class="pb-2 pr-3">Produk</th>
                                <th class="pb-2 pr-3 text-right">Qty</th>
                                <th class="pb-2 pr-3 text-right">Harga</th>
                                <th class="pb-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="py-2.5 pr-3">
                                        <span class="font-medium text-ink">{{ $item->product_name }}</span>
                                        @if($item->description)<span class="block text-xs text-neutral-500">{{ $item->description }}</span>@endif
                                    </td>
                                    <td class="py-2.5 pr-3 text-right">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                                    <td class="py-2.5 pr-3 text-right">{{ rupiah($item->unit_price) }}</td>
                                    <td class="py-2.5 text-right font-semibold">{{ rupiah($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-line">
                                <td colspan="3" class="pt-3 text-right text-xs font-bold uppercase tracking-wider text-neutral-500">Grand Total</td>
                                <td class="pt-3 text-right text-lg font-extrabold text-brand-600">{{ rupiah($order->grand_total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="space-y-5">
                <div class="admin-card">
                    <h2 class="font-extrabold text-ink">Customer</h2>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">Nama</dt><dd class="font-medium text-right">{{ $order->customer->name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">Perusahaan</dt><dd class="font-medium text-right">{{ $order->customer->company ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">WhatsApp</dt><dd class="font-medium text-right">{{ $order->customer->whatsapp ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">Email</dt><dd class="font-medium text-right">{{ $order->customer->email ?? '—' }}</dd></div>
                    </dl>
                    @if($order->customer->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->customer->whatsapp) }}?text={{ rawurlencode('Halo '.$order->customer->name.', update pesanan '.$order->order_number.' Anda: lacak di '.route('tracking.index', ['order' => $order->order_number])) }}"
                           target="_blank" rel="noopener" class="btn-wa mt-4 w-full !py-2.5 text-xs">Kirim Link Tracking via WA</a>
                    @endif
                </div>
                <div class="admin-card">
                    <h2 class="font-extrabold text-ink">Jadwal</h2>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">Deadline (internal)</dt><dd class="font-medium">{{ $order->deadline?->translatedFormat('d F Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">Estimasi selesai (publik)</dt><dd class="font-medium">{{ $order->estimated_completion?->translatedFormat('d F Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-neutral-500">DP diminta</dt><dd class="font-medium">{{ $order->dp_amount ? rupiah($order->dp_amount) : '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ TRACKING ============ --}}
    @if($tab === 'tracking')
        <div class="admin-card max-w-3xl">
            <h2 class="font-extrabold text-ink">Tracking Progress — 7 Tahap</h2>
            <p class="mt-1 text-sm text-neutral-500">Selesaikan tahap yang sedang berjalan; tahap berikutnya otomatis dimulai.</p>
            <ol class="mt-6">
                @foreach($order->stages as $stage)
                    <li class="relative flex gap-4 pb-7 {{ $loop->last ? '!pb-0' : '' }}">
                        @unless($loop->last)
                            <span class="absolute left-[15px] top-8 h-full w-0.5 {{ $stage->isCompleted() ? 'bg-green-500' : 'bg-line' }}"></span>
                        @endunless

                        @if($stage->isCompleted())
                            <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-500 text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                        @elseif($stage->isInProgress())
                            <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50">
                                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-amber-500"></span>
                            </span>
                        @else
                            <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-line bg-white">
                                <span class="text-xs font-bold text-neutral-400">{{ $stage->stage_number }}</span>
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <h3 class="font-bold {{ $stage->isPending() ? 'text-neutral-400' : 'text-ink' }}">{{ $stage->name }}</h3>
                                    @if($stage->isCompleted())
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Selesai</span>
                                    @elseif($stage->isInProgress())
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Sedang Diproses</span>
                                    @endif
                                </div>
                                <div class="flex gap-1.5">
                                    @if($stage->isPending())
                                        <form method="POST" action="{{ route('admin.orders.stages.start', [$order, $stage]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-neutral-600 transition hover:border-amber-400 hover:text-amber-600">Mulai Tahap</button>
                                        </form>
                                    @elseif($stage->isInProgress())
                                        <form method="POST" action="{{ route('admin.orders.stages.complete', [$order, $stage]) }}" class="flex gap-1.5">
                                            @csrf
                                            <input type="text" name="note" placeholder="Catatan (opsional)" class="form-input !w-44 !px-2.5 !py-1.5 text-xs">
                                            <button type="submit" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">Selesaikan Tahap</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.orders.stages.reopen', [$order, $stage]) }}"
                                              onsubmit="return confirm('Buka kembali tahap ini? Tahap setelahnya akan dikembalikan ke Menunggu.')">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-neutral-500 transition hover:border-brand-600 hover:text-brand-600">Buka Kembali</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-neutral-500">
                                @if($stage->started_at)Dimulai {{ $stage->started_at->format('d/m/Y H:i') }}@endif
                                @if($stage->completed_at) &bull; Selesai {{ $stage->completed_at->format('d/m/Y H:i') }}@endif
                                @if($stage->updater) &bull; oleh {{ $stage->updater->name }}@endif
                            </p>
                            @if($stage->note)
                                <p class="mt-1.5 rounded-lg bg-cream px-3 py-2 text-sm text-neutral-600">{{ $stage->note }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- ============ PHOTOS ============ --}}
    @if($tab === 'photos')
        <div class="grid gap-5 lg:grid-cols-3">
            <form method="POST" action="{{ route('admin.orders.photos.store', $order) }}" enctype="multipart/form-data" class="admin-card h-fit">
                @csrf
                <h2 class="font-extrabold text-ink">Upload Foto Produksi</h2>
                <div class="mt-4">
                    <label class="form-label">Foto (bisa lebih dari satu)</label>
                    <x-admin.media-picker name="photos" :multiple="true" />
                </div>
                <div class="mt-4">
                    <label class="form-label">Tahap</label>
                    <select class="form-input" name="stage_number">
                        @foreach(\App\Support\Stages::NAMES as $num => $name)
                            <option value="{{ $num }}" @selected($num === $order->current_stage)>{{ $num }}. {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-4">
                    <label class="form-label">Caption (opsional)</label>
                    <input class="form-input" type="text" name="caption" maxlength="300">
                </div>
                <div class="mt-4">
                    <label class="form-label">Visibilitas</label>
                    <div class="space-y-2 text-sm">
                        <label class="flex items-center gap-2"><input type="radio" name="visibility" value="internal" checked class="text-brand-600"> Internal (hanya admin)</label>
                        <label class="flex items-center gap-2"><input type="radio" name="visibility" value="public" class="text-brand-600"> Public (tampil di tracking customer)</label>
                    </div>
                </div>
                <button type="submit" class="btn-primary mt-5 w-full">Upload Foto</button>
            </form>

            <div class="lg:col-span-2">
                @forelse($order->productionPhotos->groupBy('stage_number')->sortKeys() as $stageNumber => $photos)
                    <div class="admin-card mb-5">
                        <h3 class="font-bold text-ink">{{ $stageNumber }}. {{ \App\Support\Stages::name($stageNumber) }}</h3>
                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach($photos as $photo)
                                <div class="overflow-hidden rounded-lg border border-line">
                                    <a href="{{ route('admin.photos.file', [$photo, 'full']) }}" target="_blank">
                                        <img src="{{ route('admin.photos.file', [$photo, 'thumb']) }}" alt="{{ $photo->caption }}" loading="lazy" class="aspect-square w-full object-cover">
                                    </a>
                                    <div class="p-2.5">
                                        @if($photo->caption)<p class="truncate text-xs text-neutral-600">{{ $photo->caption }}</p>@endif
                                        <div class="mt-1.5 flex items-center justify-between gap-2">
                                            <form method="POST" action="{{ route('admin.photos.update', $photo) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="visibility" value="{{ $photo->isPublic() ? 'internal' : 'public' }}">
                                                <button type="submit"
                                                        class="rounded-full px-2.5 py-1 text-[11px] font-bold transition {{ $photo->isPublic() ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }}"
                                                        title="Klik untuk ubah visibilitas">
                                                    {{ $photo->isPublic() ? '● Public' : '○ Internal' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="admin-card py-10 text-center text-neutral-500">Belum ada foto produksi. Foto baru default-nya <strong>Internal</strong> — ubah ke Public agar tampil di tracking customer.</div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- ============ INVOICE ============ --}}
    @if($tab === 'invoice')
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold text-ink">Invoice</h2>
                <a href="{{ route('admin.invoices.create', ['order' => $order->id]) }}" class="btn-primary !px-4 !py-2 text-xs">+ Buat Invoice</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                            <th class="pb-2.5 pr-4">No. Invoice</th>
                            <th class="pb-2.5 pr-4">Tanggal</th>
                            <th class="pb-2.5 pr-4">Jatuh Tempo</th>
                            <th class="pb-2.5 pr-4 text-right">Grand Total</th>
                            <th class="pb-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($order->invoices as $invoice)
                            <tr>
                                <td class="py-3 pr-4"><a href="{{ route('admin.invoices.show', $invoice) }}" class="font-mono font-bold text-brand-600 hover:underline">{{ $invoice->invoice_number }}</a></td>
                                <td class="py-3 pr-4">{{ $invoice->date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-right font-semibold">{{ rupiah($invoice->grand_total) }}</td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="text-sm font-semibold text-brand-600 hover:underline">Unduh PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-neutral-500">Belum ada invoice untuk pesanan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ PAYMENTS ============ --}}
    @if($tab === 'payments')
        <div class="grid gap-5 lg:grid-cols-3">
            <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}" enctype="multipart/form-data" class="admin-card h-fit">
                @csrf
                <h2 class="font-extrabold text-ink">Catat Pembayaran</h2>
                <div class="mt-3 rounded-lg bg-cream p-3.5 text-sm">
                    <div class="flex justify-between"><span class="text-neutral-500">Grand Total</span><span class="font-bold">{{ rupiah($order->grand_total) }}</span></div>
                    <div class="mt-1 flex justify-between"><span class="text-neutral-500">Terbayar</span><span class="font-bold text-green-600">{{ rupiah($order->amount_paid) }}</span></div>
                    <div class="mt-1 flex justify-between border-t border-line pt-1"><span class="text-neutral-500">Sisa</span><span class="font-bold text-brand-600">{{ rupiah($order->remaining) }}</span></div>
                </div>
                <div class="mt-4">
                    <label class="form-label">Nominal (Rp) <span class="text-brand-600">*</span></label>
                    <input class="form-input" type="number" name="amount" min="1" value="{{ old('amount', $order->remaining ?: '') }}" required>
                </div>
                <div class="mt-4">
                    <label class="form-label">Tanggal Bayar <span class="text-brand-600">*</span></label>
                    <input class="form-input" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                </div>
                <div class="mt-4">
                    <label class="form-label">Metode</label>
                    <select class="form-input" name="method">
                        @foreach(\App\Models\Payment::METHODS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($order->invoices->isNotEmpty())
                    <div class="mt-4">
                        <label class="form-label">Terkait Invoice</label>
                        <select class="form-input" name="invoice_id">
                            <option value="">—</option>
                            @foreach($order->invoices as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="mt-4">
                    <label class="form-label">Referensi</label>
                    <input class="form-input" type="text" name="reference" placeholder="No. transaksi / berita transfer">
                </div>
                <div class="mt-4">
                    <label class="form-label">Bukti Pembayaran</label>
                    <input class="form-input !py-2" type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf">
                </div>
                <div class="mt-4">
                    <label class="form-label">Catatan</label>
                    <input class="form-input" type="text" name="note" placeholder="Contoh: DP 50%">
                </div>
                <button type="submit" class="btn-primary mt-5 w-full">Catat Pembayaran</button>
            </form>

            <div class="admin-card h-fit lg:col-span-2">
                <h2 class="font-extrabold text-ink">Riwayat Pembayaran</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                                <th class="pb-2.5 pr-4">Tanggal</th>
                                <th class="pb-2.5 pr-4 text-right">Nominal</th>
                                <th class="pb-2.5 pr-4">Metode</th>
                                <th class="pb-2.5 pr-4">Catatan</th>
                                <th class="pb-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($order->payments as $payment)
                                <tr>
                                    <td class="py-3 pr-4">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="py-3 pr-4 text-right font-bold text-green-600">{{ rupiah($payment->amount) }}</td>
                                    <td class="py-3 pr-4">{{ $payment->method_label }}@if($payment->reference)<span class="block text-xs text-neutral-500">{{ $payment->reference }}</span>@endif</td>
                                    <td class="py-3 pr-4 text-neutral-600">{{ $payment->note ?? '—' }}</td>
                                    <td class="py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if($payment->proof_path)
                                                <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank" class="text-xs font-semibold text-brand-600 hover:underline">Bukti</a>
                                            @endif
                                            <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}" onsubmit="return confirm('Hapus pembayaran ini? Status pembayaran akan dihitung ulang.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-neutral-500">Belum ada pembayaran tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ FILES ============ --}}
    @if($tab === 'files')
        <div class="grid gap-5 lg:grid-cols-3">
            <form method="POST" action="{{ route('admin.orders.attachments.store', $order) }}" enctype="multipart/form-data" class="admin-card h-fit">
                @csrf
                <h2 class="font-extrabold text-ink">Upload File</h2>
                <div class="mt-4">
                    <label class="form-label">File <span class="text-brand-600">*</span></label>
                    <input class="form-input !py-2" type="file" name="file" required>
                    <p class="mt-1 text-xs text-neutral-500">Maks 10 MB. Semua file bersifat internal (tidak tampil di tracking).</p>
                </div>
                <div class="mt-4">
                    <label class="form-label">Kategori</label>
                    <select class="form-input" name="category">
                        @foreach(\App\Models\OrderAttachment::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary mt-5 w-full">Upload</button>
            </form>

            <div class="admin-card h-fit lg:col-span-2">
                <h2 class="font-extrabold text-ink">File Pesanan</h2>
                <ul class="mt-4 divide-y divide-line text-sm">
                    @forelse($order->attachments as $attachment)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <a href="{{ route('admin.attachments.download', $attachment) }}" class="block truncate font-semibold text-brand-600 hover:underline">{{ $attachment->original_name }}</a>
                                <p class="text-xs text-neutral-500">{{ $attachment->category_label }} &bull; {{ number_format($attachment->size / 1024, 0) }} KB &bull; {{ $attachment->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.attachments.destroy', $attachment) }}" onsubmit="return confirm('Hapus file ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-8 text-center text-neutral-500">Belum ada file.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif

    {{-- ============ HISTORY ============ --}}
    @if($tab === 'history')
        <div class="admin-card max-w-3xl">
            <h2 class="font-extrabold text-ink">Riwayat Aktivitas</h2>
            <ul class="mt-4 divide-y divide-line text-sm">
                @forelse($order->activities as $activity)
                    <li class="flex items-start justify-between gap-4 py-3">
                        <span>{{ $activity->description }}</span>
                        <span class="shrink-0 text-xs text-neutral-500">
                            {{ $activity->created_at->format('d/m/Y H:i') }}
                            @if($activity->user) &bull; {{ $activity->user->name }}@endif
                        </span>
                    </li>
                @empty
                    <li class="py-8 text-center text-neutral-500">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </div>
    @endif

    {{-- ============ NOTES ============ --}}
    @if($tab === 'notes')
        <form method="POST" action="{{ route('admin.orders.notes', $order) }}" class="admin-card max-w-3xl">
            @csrf @method('PATCH')
            <h2 class="font-extrabold text-ink">Catatan Internal</h2>
            <p class="mt-1 text-sm text-neutral-500">Hanya terlihat oleh admin — tidak pernah tampil di halaman tracking customer.</p>
            <textarea class="form-input mt-4" name="notes" rows="8">{{ old('notes', $order->notes) }}</textarea>
            <button type="submit" class="btn-primary mt-4">Simpan Catatan</button>
        </form>
    @endif
</div>
@endsection

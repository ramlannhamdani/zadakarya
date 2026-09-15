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
            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}"
                  onsubmit="return confirm('Hapus pesanan {{ $order->order_number }}?\n\nInvoice, pembayaran ({{ rupiah($order->amount_paid) }}), foto produksi, dan file pesanan ini ikut terhapus permanen — total pendapatan di dashboard akan berkurang.\n\nUntuk pesanan yang batal tapi datanya ingin disimpan, ubah status jadi Dibatalkan.')">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 transition hover:border-red-400 hover:bg-red-50">Hapus Pesanan</button>
            </form>
        </div>
    </div>

    <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-5 sm:grid-cols-3 lg:grid-cols-5">
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Grand Total</dt>
            <dd class="mt-1 whitespace-nowrap text-lg font-extrabold text-ink">{{ rupiah($order->grand_total) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Terbayar</dt>
            <dd class="mt-1 whitespace-nowrap text-lg font-extrabold text-green-600">{{ rupiah($order->amount_paid) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Sisa</dt>
            <dd class="mt-1 whitespace-nowrap text-lg font-extrabold {{ $order->remaining > 0 ? 'text-brand-600' : 'text-neutral-400' }}">{{ rupiah($order->remaining) }}</dd>
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

    @php $invoicedTotal = (int) $order->invoices->sum('grand_total'); @endphp
    @if($order->invoices->isNotEmpty() && $invoicedTotal !== $order->grand_total)
        {{-- Grand Total berasal dari Item Produk, sedangkan invoice punya itemnya
             sendiri — kalau berbeda, angka Sisa & status pembayaran jadi menyesatkan. --}}
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Total invoice ({{ rupiah($invoicedTotal) }}) tidak sama dengan Grand Total pesanan ({{ rupiah($order->grand_total) }}).</p>
            <p class="mt-1 text-amber-800">Grand Total dihitung dari <strong>Item Produk</strong> di tab Overview, bukan dari invoice. Selama keduanya berbeda, kolom <strong>Sisa</strong> dan status pembayaran di atas ikut tidak akurat. Samakan itemnya lewat <a href="{{ route('admin.orders.edit', $order) }}" class="font-semibold underline">Edit Pesanan</a>, atau pisahkan pekerjaan yang berbeda menjadi pesanan tersendiri.</p>
        </div>
    @endif
</div>

{{-- Tabs --}}
@php
    $tabs = [
        'overview' => 'Overview',
        'tracking' => 'Tracking',
        'photos' => 'Foto Produksi',
        'invoice' => 'Invoice',
        'payments' => 'Pembayaran',
        'hpp' => 'Biaya & HPP',
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
            @if($key === 'hpp' && $order->costs->count())<span class="ml-1 rounded-full bg-neutral-100 px-1.5 text-xs">{{ $order->costs->count() }}</span>@endif
            @if($key === 'files' && $order->attachments->count())<span class="ml-1 rounded-full bg-neutral-100 px-1.5 text-xs">{{ $order->attachments->count() }}</span>@endif
        </a>
    @endforeach
</div>

<div class="mt-5">
    {{-- ============ OVERVIEW ============ --}}
    @if($tab === 'overview')
        <div class="grid gap-5 xl:grid-cols-2">
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Item Produk</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[30rem] text-sm">
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
                                    <td class="whitespace-nowrap py-2.5 pr-3 text-right">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                                    <td class="whitespace-nowrap py-2.5 pr-3 text-right">{{ rupiah($item->unit_price) }}</td>
                                    <td class="whitespace-nowrap py-2.5 text-right font-semibold">{{ rupiah($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if($order->discount > 0)
                                <tr class="border-t-2 border-line text-neutral-600">
                                    <td colspan="3" class="pt-3 text-right text-xs font-bold uppercase tracking-wider text-neutral-500">Subtotal</td>
                                    <td class="whitespace-nowrap pt-3 text-right font-semibold text-ink">{{ rupiah($order->subtotal ?: $order->items->sum('total')) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="py-1 text-right text-xs font-bold uppercase tracking-wider text-neutral-500">Diskon</td>
                                    <td class="whitespace-nowrap py-1 text-right font-semibold text-red-500">- {{ rupiah($order->discount) }}</td>
                                </tr>
                                <tr class="border-t border-line">
                                    <td colspan="3" class="pt-2 text-right text-xs font-bold uppercase tracking-wider text-neutral-500">Grand Total</td>
                                    <td class="whitespace-nowrap pt-2 text-right text-lg font-extrabold text-brand-600">{{ rupiah($order->grand_total) }}</td>
                                </tr>
                            @else
                                <tr class="border-t-2 border-line">
                                    <td colspan="3" class="pt-3 text-right text-xs font-bold uppercase tracking-wider text-neutral-500">Grand Total</td>
                                    <td class="whitespace-nowrap pt-3 text-right text-lg font-extrabold text-brand-600">{{ rupiah($order->grand_total) }}</td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-1">
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

                <div class="admin-card">
                    <div class="flex items-center justify-between">
                        <h2 class="font-extrabold text-ink">HPP & Profitabilitas</h2>
                        <a href="{{ route('admin.orders.show', ['order' => $order, 'tab' => 'hpp']) }}" class="text-xs font-semibold text-brand-600 hover:underline">Kelola &rarr;</a>
                    </div>
                    @if($order->costs->isNotEmpty())
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-500">Total HPP</dt>
                                <dd class="font-bold text-ink">{{ rupiah($order->total_cost) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-500">HPP / Pcs</dt>
                                <dd class="font-medium text-neutral-600">{{ $order->total_quantity > 0 ? rupiah($order->cost_per_unit).'/pcs' : '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-500">Laba Kotor</dt>
                                <dd class="font-bold {{ $order->gross_profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ rupiah($order->gross_profit) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-neutral-500">Margin</dt>
                                <dd class="font-bold {{ $order->profit_margin >= 20 ? 'text-emerald-600' : ($order->profit_margin >= 0 ? 'text-amber-600' : 'text-rose-600') }}">{{ $order->profit_margin }}%</dd>
                            </div>
                        </dl>
                    @else
                        <div class="mt-3 rounded-lg border border-dashed border-line p-3 text-center">
                            <p class="text-xs text-neutral-500">Belum ada biaya riil yang dicatat.</p>
                            <a href="{{ route('admin.orders.show', ['order' => $order, 'tab' => 'hpp']) }}" class="mt-1.5 inline-block text-xs font-semibold text-brand-600 hover:underline">+ Catat Biaya Produksi</a>
                        </div>
                    @endif
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
                                        <form method="POST" action="{{ route('admin.orders.stages.complete', [$order, $stage]) }}" class="flex w-full gap-1.5 sm:w-auto">
                                            @csrf
                                            <input type="text" name="note" placeholder="Catatan (opsional)" class="form-input min-w-0 flex-1 !px-2.5 !py-1.5 text-xs sm:!w-44 sm:flex-none">
                                            <button type="submit" class="whitespace-nowrap rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">Selesaikan Tahap</button>
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
                <a href="{{ route('admin.invoices.create', ['order' => $order->id]) }}" class="btn-primary !px-4 !py-2 text-xs">{{ $order->invoices->isNotEmpty() ? '+ Invoice Tambahan' : '+ Buat Invoice' }}</a>
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
                                <td class="py-3 pr-4"><a href="{{ route('admin.invoices.show', $invoice) }}" class="whitespace-nowrap font-mono font-bold text-brand-600 hover:underline">{{ $invoice->invoice_number }}</a></td>
                                <td class="py-3 pr-4">{{ $invoice->date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right font-semibold">{{ rupiah($invoice->grand_total) }}</td>
                                <td class="py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        {{-- setRelation: pesanannya sudah di tangan, jadi tidak perlu query ulang per baris. --}}
                                        <x-admin.invoice-share :invoice="$invoice->setRelation('order', $order)" label="WA" small />
                                        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="text-sm font-semibold text-brand-600 hover:underline">Unduh PDF</a>
                                    </div>
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
                    @php $multiInvoice = $order->invoices->count() > 1; @endphp
                    <div class="mt-4">
                        <label class="form-label">Terkait Invoice @if($multiInvoice)<span class="text-brand-600">*</span>@endif</label>
                        <select class="form-input" name="invoice_id" @required($multiInvoice)>
                            <option value="">—</option>
                            @foreach($order->invoices as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} — {{ rupiah($invoice->grand_total) }}</option>
                            @endforeach
                        </select>
                        @if($multiInvoice)
                            <p class="mt-1 text-xs text-neutral-500">Pesanan ini punya {{ $order->invoices->count() }} invoice — pilih invoice yang dibayar agar angka Terbayar tiap invoice tidak tercampur.</p>
                        @endif
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
                    <table class="w-full min-w-[500px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                                <th class="pb-2.5 pr-4">Tanggal</th>
                                <th class="pb-2.5 pr-4 text-right">Nominal</th>
                                <th class="pb-2.5 pr-4">Metode</th>
                                @if($order->invoices->count() > 1)<th class="pb-2.5 pr-4">Untuk Invoice</th>@endif
                                <th class="pb-2.5 pr-4">Catatan</th>
                                <th class="pb-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($order->payments as $payment)
                                <tr>
                                    <td class="py-3 pr-4">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap py-3 pr-4 text-right font-bold text-green-600">{{ rupiah($payment->amount) }}</td>
                                    <td class="py-3 pr-4">{{ $payment->method_label }}@if($payment->reference)<span class="block text-xs text-neutral-500">{{ $payment->reference }}</span>@endif</td>
                                    @if($order->invoices->count() > 1)
                                        {{-- Bisa ditautkan ulang tanpa menghapus pembayaran --}}
                                        <td class="py-3 pr-4">
                                            <form method="POST" action="{{ route('admin.payments.link', $payment) }}">
                                                @csrf @method('PATCH')
                                                <select name="invoice_id" class="form-input !w-auto !py-1.5 text-xs {{ $payment->invoice_id ? '' : '!border-amber-300 !bg-amber-50' }}" onchange="this.form.submit()">
                                                    <option value="">— belum ditautkan —</option>
                                                    @foreach($order->invoices as $inv)
                                                        <option value="{{ $inv->id }}" @selected($payment->invoice_id === $inv->id)>{{ $inv->invoice_number }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                    @endif
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
                                <tr><td colspan="{{ $order->invoices->count() > 1 ? 6 : 5 }}" class="py-8 text-center text-neutral-500">Belum ada pembayaran tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ BIAYA & HPP ============ --}}
    @if($tab === 'hpp')
        @php
            $costsByCategory = $order->costs->groupBy('category');
            $totalCost = $order->total_cost;
            $grandTotal = $order->grand_total;
            $grossProfit = $order->gross_profit;
            $margin = $order->profit_margin;
            $totalQty = $order->total_quantity;
            $costPerUnit = $order->cost_per_unit;
            $pricePerUnit = $totalQty > 0 ? (int) round($grandTotal / $totalQty) : 0;
            $profitPerUnit = $totalQty > 0 ? (int) round($grossProfit / $totalQty) : 0;
            $statusMeta = $order->profit_status_meta;
        @endphp

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-card">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Nilai Pesanan (Omset)</span>
                <p class="mt-2 text-2xl font-extrabold text-ink">{{ rupiah($grandTotal) }}</p>
                <p class="mt-1 text-xs text-neutral-500">
                    @if($totalQty > 0)
                        {{ $totalQty }} pcs ({{ rupiah($pricePerUnit) }}/pcs)
                    @else
                        Total item produk belum diset
                    @endif
                </p>
            </div>

            <div class="admin-card">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Biaya Produksi (HPP)</span>
                <p class="mt-2 text-2xl font-extrabold text-brand-600">{{ rupiah($totalCost) }}</p>
                <p class="mt-1 text-xs text-neutral-500">
                    @if($totalQty > 0 && $totalCost > 0)
                        HPP per pcs: <strong class="text-ink">{{ rupiah($costPerUnit) }}</strong>
                    @else
                        {{ $order->costs->count() }} pengeluaran tercatat
                    @endif
                </p>
            </div>

            <div class="admin-card">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Laba Kotor (Gross Profit)</span>
                <p class="mt-2 text-2xl font-extrabold {{ $grossProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ rupiah($grossProfit) }}
                </p>
                <p class="mt-1 text-xs text-neutral-500">
                    @if($totalQty > 0 && $order->costs->isNotEmpty())
                        Laba per pcs: <strong class="{{ $profitPerUnit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ rupiah($profitPerUnit) }}</strong>
                    @else
                        Omset dikurangi seluruh biaya riil
                    @endif
                </p>
            </div>

            <div class="admin-card">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Margin Keuntungan</span>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-2xl font-extrabold {{ $margin >= 20 ? 'text-emerald-600' : ($margin >= 10 ? 'text-blue-600' : ($margin >= 0 ? 'text-amber-600' : 'text-rose-600')) }}">
                        {{ $margin }}%
                    </span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $statusMeta['badge'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-neutral-500">
                    Standar konveksi sehat: margin &ge; 25%
                </p>
            </div>
        </div>

        {{-- Breakdown per Komponen Biaya --}}
        @if($totalCost > 0)
            <div class="admin-card mt-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-extrabold text-ink">Distribusi Biaya per Komponen</h2>
                        <p class="text-xs text-neutral-500">Alokasi pengeluaran riil konveksi berdasarkan total HPP {{ rupiah($totalCost) }}</p>
                    </div>
                    <span class="text-xs font-semibold text-neutral-400">5 Kategori Biaya</span>
                </div>

                {{-- Stacked Progress Bar --}}
                <div class="mt-4 flex h-3.5 w-full overflow-hidden rounded-full bg-neutral-100">
                    @foreach(\App\Models\OrderCost::CATEGORIES as $catKey => $catLabel)
                        @php
                            $catSum = (int) ($costsByCategory->get($catKey)?->sum('amount') ?? 0);
                            $catPct = $totalCost > 0 ? round(($catSum / $totalCost) * 100, 1) : 0;
                            $barColors = [
                                'kain' => 'bg-blue-500',
                                'aksesoris' => 'bg-purple-500',
                                'makloon' => 'bg-amber-500',
                                'cmt_jahit' => 'bg-emerald-500',
                                'packing_operasional' => 'bg-slate-500',
                            ];
                        @endphp
                        @if($catSum > 0)
                            <div class="{{ $barColors[$catKey] ?? 'bg-neutral-400' }}"
                                 style="width: {{ $catPct }}%"
                                 title="{{ $catLabel }}: {{ rupiah($catSum) }} ({{ $catPct }}%)"></div>
                        @endif
                    @endforeach
                </div>

                {{-- Category stats grid --}}
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach(\App\Models\OrderCost::CATEGORIES as $catKey => $catLabel)
                        @php
                            $catSum = (int) ($costsByCategory->get($catKey)?->sum('amount') ?? 0);
                            $catPct = $totalCost > 0 ? round(($catSum / $totalCost) * 100, 1) : 0;
                            $dotColors = [
                                'kain' => 'bg-blue-500',
                                'aksesoris' => 'bg-purple-500',
                                'makloon' => 'bg-amber-500',
                                'cmt_jahit' => 'bg-emerald-500',
                                'packing_operasional' => 'bg-slate-500',
                            ];
                        @endphp
                        <div class="rounded-lg border border-line p-3">
                            <div class="flex items-center gap-1.5 text-xs text-neutral-600">
                                <span class="h-2 w-2 rounded-full {{ $dotColors[$catKey] ?? 'bg-neutral-400' }}"></span>
                                <span class="truncate font-medium">{{ $catLabel }}</span>
                            </div>
                            <p class="mt-1.5 font-extrabold text-ink">{{ rupiah($catSum) }}</p>
                            <span class="text-xs text-neutral-400">{{ $catPct }}% dari HPP</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Form & Cost Table --}}
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            {{-- Form Tambah Pengeluaran --}}
            <form method="POST" action="{{ route('admin.orders.costs.store', $order) }}" enctype="multipart/form-data" class="admin-card h-fit"
                  x-data="{
                      qty: '{{ old('quantity', '') }}',
                      unitPrice: '{{ old('unit_price', '') }}',
                      amount: '{{ old('amount', '') }}',
                      recalcTotal() {
                          let q = parseFloat(String(this.qty).replace(',', '.')) || 0;
                          let p = parseInt(String(this.unitPrice).replace(/\D/g, '')) || 0;
                          if (q > 0 && p > 0) {
                              this.amount = this.formatNumber(Math.round(q * p));
                          }
                      },
                      formatNumber(val) {
                          if (!val && val !== 0) return '';
                          return String(val).replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                      }
                  }">
                @csrf
                <h2 class="font-extrabold text-ink">Catat Biaya Produksi</h2>
                <p class="mt-1 text-xs text-neutral-500">Catat pengeluaran bahan, makloon bordir/sablon, upah CMT, atau operasional order ini.</p>

                <div class="mt-4">
                    <label class="form-label">Kategori Biaya <span class="text-brand-600">*</span></label>
                    <select class="form-input" name="category" required>
                        @foreach(\App\Models\OrderCost::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <label class="form-label">Keterangan / Rincian <span class="text-brand-600">*</span></label>
                    <input class="form-input" type="text" name="description" value="{{ old('description') }}" placeholder="Mis: Upah Jahit Kaos, Kain Combed 24s Hitam" required>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Kuantitas</label>
                        <input class="form-input" type="text" name="quantity" x-model="qty" @input="recalcTotal()" placeholder="Mis: 100 / 25">
                    </div>
                    <div>
                        <label class="form-label">Satuan</label>
                        <input class="form-input" type="text" name="unit" value="{{ old('unit') }}" placeholder="pcs, kg, roll, yard">
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Harga Satuan (Rp)</label>
                        <input class="form-input font-mono" type="text" name="unit_price" x-model="unitPrice" @input="unitPrice = formatNumber(unitPrice); recalcTotal()" placeholder="Mis: 2.000">
                        <p class="mt-1 text-[11px] text-neutral-500">Contoh: Rp 2.000/pcs</p>
                    </div>
                    <div>
                        <label class="form-label">Total Biaya (Rp) <span class="text-brand-600">*</span></label>
                        <input class="form-input font-mono font-bold" type="text" name="amount" x-model="amount" @input="amount = formatNumber(amount)" placeholder="Mis: 200.000" required>
                        <p class="mt-1 text-[11px] text-neutral-500">Otomatis Qty &times; Harga Satuan</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label">Tanggal Pengeluaran <span class="text-brand-600">*</span></label>
                    <input class="form-input" type="date" name="spent_at" value="{{ old('spent_at', now()->toDateString()) }}" required>
                </div>

                <div class="mt-4">
                    <label class="form-label">Foto Nota / Bukti Struk</label>
                    <input class="form-input !py-2" type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <p class="mt-1 text-xs text-neutral-500">Format JPG, PNG, WEBP, atau PDF (maks. 5MB).</p>
                </div>

                <button type="submit" class="btn-primary mt-5 w-full">Simpan Biaya</button>
            </form>

            {{-- Daftar Pengeluaran --}}
            <div class="admin-card h-fit lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-extrabold text-ink">Rincian Pengeluaran Terdata</h2>
                        <p class="text-xs text-neutral-500">Semua pengeluaran yang membentuk HPP pesanan ini</p>
                    </div>
                    <span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-600">{{ $order->costs->count() }} pengeluaran</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[550px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                                <th class="pb-2.5 pr-3">Tanggal</th>
                                <th class="pb-2.5 pr-3">Kategori</th>
                                <th class="pb-2.5 pr-3">Keterangan</th>
                                <th class="pb-2.5 pr-3 text-right">Nominal</th>
                                <th class="pb-2.5 pr-3 text-center">Nota</th>
                                <th class="pb-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($order->costs as $cost)
                                <tr>
                                    <td class="whitespace-nowrap py-3 pr-3 text-xs text-neutral-600">
                                        {{ $cost->spent_at->format('d/m/Y') }}
                                    </td>
                                    <td class="whitespace-nowrap py-3 pr-3">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $cost->category_badge_class }}">
                                            {{ $cost->category_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <p class="font-semibold text-ink">{{ $cost->description }}</p>
                                        @if($cost->quantity && $cost->unit_price)
                                            <p class="text-xs text-neutral-500 font-medium">
                                                {{ rtrim(rtrim(number_format($cost->quantity, 2, ',', '.'), '0'), ',') }} {{ $cost->unit ?: 'unit' }} &times; {{ rupiah($cost->unit_price) }}
                                            </p>
                                        @elseif($cost->quantity)
                                            <p class="text-xs text-neutral-500">{{ rtrim(rtrim(number_format($cost->quantity, 2, ',', '.'), '0'), ',') }} {{ $cost->unit }}</p>
                                        @endif
                                        @if($cost->recorder)
                                            <p class="text-[11px] text-neutral-400">Dicatat oleh {{ $cost->recorder->name }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap py-3 pr-3 text-right font-mono font-bold text-ink">
                                        {{ rupiah($cost->amount) }}
                                    </td>
                                    <td class="whitespace-nowrap py-3 pr-3 text-center">
                                        @if($cost->receipt_path)
                                            <a href="{{ route('admin.orders.costs.receipt', $cost) }}" target="_blank" class="inline-flex items-center gap-1 rounded bg-neutral-100 px-2 py-1 text-xs font-semibold text-brand-600 hover:bg-neutral-200">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Lihat
                                            </a>
                                        @else
                                            <span class="text-xs text-neutral-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        <form method="POST" action="{{ route('admin.orders.costs.destroy', [$order, $cost]) }}" onsubmit="return confirm('Hapus catatan pengeluaran {{ rupiah($cost->amount) }} ({{ $cost->description }})?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-neutral-500">
                                        <p class="font-medium">Belum ada biaya produksi yang dicatat.</p>
                                        <p class="mt-1 text-xs text-neutral-400">Catat pengeluaran bahan baku, makloon, atau upah CMT melalui formulir di samping untuk menganalisis HPP dan laba kotor pesanan ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($order->costs->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-line font-bold text-ink">
                                    <td colspan="3" class="pt-3 text-right uppercase text-xs tracking-wider text-neutral-500">Total HPP:</td>
                                    <td class="pt-3 text-right font-mono text-base text-brand-600">{{ rupiah($totalCost) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
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

@extends('layouts.site')

@section('title', 'Lacak Pesanan — '.setting('company_name'))
@section('meta_description', 'Lacak progress produksi pesanan Anda di Zada Karya Production menggunakan nomor pesanan (contoh: ZDK-0001).')

@section('content')
<section class="border-b border-line bg-cream">
    <div class="mx-auto max-w-3xl px-4 py-14 text-center sm:px-6 lg:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-warm-600">Tracking</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-ink">Lacak Pesanan Anda</h1>
        <p class="mt-4 text-neutral-600">Masukkan nomor pesanan Anda (contoh: <span class="font-mono font-semibold text-ink">ZDK-0001</span>) untuk melihat progress produksi.</p>

        <form method="GET" action="{{ route('tracking.index') }}" class="mx-auto mt-8 flex max-w-md gap-2"
              onsubmit="if(window.gtag){gtag('event','tracking_search');}">
            <input type="text" name="order" value="{{ $number }}" placeholder="ZDK-0001"
                   class="form-input flex-1 !py-3 text-center font-mono text-base uppercase tracking-widest" required>
            <button type="submit" class="btn-primary !px-6">Lacak Pesanan</button>
        </form>
    </div>
</section>

<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    @if($notFound)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
            <h2 class="text-xl font-bold text-ink">Pesanan tidak ditemukan</h2>
            <p class="mt-2 text-neutral-600">Nomor <span class="font-mono font-semibold">{{ $number }}</span> tidak terdaftar. Periksa kembali nomor pesanan Anda, atau hubungi kami via WhatsApp.</p>
            <a href="{{ wa_link('Halo Zada Karya Production, saya ingin menanyakan status pesanan saya.') }}" target="_blank" rel="noopener" class="btn-wa mt-5">Tanya via WhatsApp</a>
        </div>
    @elseif($order)
        {{-- Order summary --}}
        <div class="rounded-xl border border-line bg-white p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-neutral-500">Nomor Pesanan</p>
                    <p class="mt-1 font-mono text-2xl font-extrabold text-brand-600">{{ $order->order_number }}</p>
                    <p class="mt-1.5 font-semibold text-ink">{{ $order->name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold uppercase tracking-widest text-neutral-500">Status Pembayaran</p>
                    <span class="mt-1.5 inline-block rounded-full px-3.5 py-1.5 text-sm font-bold
                        {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($order->payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600') }}">
                        {{ $order->payment_status_label }}
                    </span>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                            <th class="px-4 py-2.5">Produk</th>
                            <th class="px-4 py-2.5 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($order->items as $item)
                            <tr>
                                <td class="px-4 py-3 font-medium text-ink">{{ $item->product_name }}</td>
                                <td class="px-4 py-3 text-right text-neutral-600">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($order->estimated_completion)
                <p class="mt-4 flex items-center gap-2 text-sm text-neutral-600">
                    <svg class="h-4 w-4 text-warm-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    Estimasi selesai: <span class="font-semibold text-ink">{{ $order->estimated_completion->translatedFormat('d F Y') }}</span>
                </p>
            @endif
        </div>

        {{-- Timeline 7 tahap --}}
        <div class="mt-8 rounded-xl border border-line bg-white p-6 sm:p-8">
            <h2 class="text-lg font-extrabold text-ink">Progress Produksi</h2>
            <ol class="mt-6">
                @foreach($order->stages as $stage)
                    <li class="relative flex gap-4 pb-8 {{ $loop->last ? '!pb-0' : '' }}">
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
                                <span class="h-2 w-2 rounded-full bg-line"></span>
                            </span>
                        @endif

                        <div class="min-w-0 flex-1 pt-0.5">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <h3 class="font-bold {{ $stage->isPending() ? 'text-neutral-400' : 'text-ink' }}">{{ $stage->name }}</h3>
                                @if($stage->isCompleted())
                                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700">Selesai</span>
                                @elseif($stage->isInProgress())
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700">Sedang Diproses</span>
                                @else
                                    <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-500">Menunggu Proses</span>
                                @endif
                            </div>
                            @if($stage->isCompleted() && $stage->completed_at)
                                <p class="mt-1 text-xs text-neutral-500">Selesai {{ $stage->completed_at->translatedFormat('d M Y H:i') }}</p>
                            @elseif($stage->isInProgress() && $stage->started_at)
                                <p class="mt-1 text-xs text-neutral-500">Dimulai {{ $stage->started_at->translatedFormat('d M Y H:i') }}</p>
                            @endif
                            @if($stage->note && !$stage->isPending())
                                <p class="mt-1.5 rounded-lg bg-cream px-3 py-2 text-sm text-neutral-600">{{ $stage->note }}</p>
                            @endif

                            {{-- Public photos for this stage --}}
                            @php $stagePhotos = $order->publicPhotos->where('stage_number', $stage->stage_number); @endphp
                            @if($stagePhotos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
                                    @foreach($stagePhotos as $photo)
                                        <a href="{{ route('tracking.photo', $photo) }}" target="_blank" rel="noopener" class="group relative">
                                            <img src="{{ route('tracking.photo', ['photo' => $photo, 'thumb' => 1]) }}"
                                                 alt="{{ $photo->caption ?: 'Foto progress '.$stage->name }}"
                                                 loading="lazy"
                                                 class="aspect-square w-full rounded-lg border border-line object-cover transition group-hover:opacity-90">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <p class="mt-6 text-center text-sm text-neutral-500">
            Ada pertanyaan tentang pesanan ini?
            <a href="{{ wa_link('Halo Zada Karya Production, saya ingin menanyakan pesanan '.$order->order_number.'.') }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">Hubungi kami via WhatsApp</a>
        </p>
    @else
        <div class="grid gap-5 sm:grid-cols-3">
            @foreach([
                ['step' => '1', 'title' => 'Masukkan Nomor', 'desc' => 'Gunakan nomor pesanan yang Anda terima dari admin, contoh ZDK-0001.'],
                ['step' => '2', 'title' => 'Lihat Progress', 'desc' => 'Pantau 7 tahap produksi pesanan Anda secara real-time.'],
                ['step' => '3', 'title' => 'Foto Produksi', 'desc' => 'Lihat foto progress produksi yang dibagikan oleh tim kami.'],
            ] as $info)
                <div class="rounded-xl border border-line bg-white p-6 text-center">
                    <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 font-extrabold text-white">{{ $info['step'] }}</span>
                    <h3 class="mt-3 font-bold text-ink">{{ $info['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-neutral-600">{{ $info['desc'] }}</p>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection

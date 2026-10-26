@extends('layouts.admin')

@section('title', $customer->name)

@section('header-actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.orders.create', ['customer' => $customer->id]) }}"
       class="inline-flex items-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Buat Pesanan</span>
    </a>
    <a href="{{ route('admin.customers.edit', $customer) }}"
       class="inline-flex items-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors">
        <i class="fa-solid fa-pen-to-square text-[11px]"></i>
        <span>Edit Customer</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Daftar Pelanggan</span>
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-3">
        <!-- LEFT: CUSTOMER PROFILE CARD -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6">
            <div class="flex items-center gap-3.5 pb-5 border-b border-slate-100">
                @php
                    $words = explode(' ', trim($customer->name));
                    $initials = '';
                    foreach (array_slice($words, 0, 2) as $w) {
                        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
                    }
                @endphp
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-700 flex items-center justify-center font-bold text-sm shrink-0">
                    {{ $initials ?: 'C' }}
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-slate-900 text-sm tracking-tight truncate">{{ $customer->name }}</h3>
                    <p class="text-[11px] text-slate-400 truncate">{{ $customer->company ?: 'Pelanggan Individu' }}</p>
                </div>
            </div>

            <dl class="space-y-3.5 text-xs">
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nomor WhatsApp</dt>
                    <dd class="mt-1 flex items-center justify-between">
                        <span class="font-semibold text-slate-800 font-mono">{{ $customer->whatsapp ?: '—' }}</span>
                        @if($customer->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $customer->whatsapp) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold text-[11px] transition-colors">
                                <i class="fa-brands fa-whatsapp text-xs"></i>
                                <span>Chat WA</span>
                            </a>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Alamat Email</dt>
                    <dd class="mt-0.5 font-medium text-slate-700">{{ $customer->email ?: '—' }}</dd>
                </div>

                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kota / Alamat</dt>
                    <dd class="mt-0.5 text-slate-700 leading-relaxed">{{ $customer->address ?: '—' }}{{ $customer->city ? ', '.$customer->city : '' }}</dd>
                </div>

                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Catatan Khusus</dt>
                    <dd class="mt-0.5 text-slate-600 whitespace-pre-line">{{ $customer->notes ?: '—' }}</dd>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Terdaftar Sejak</dt>
                    <dd class="mt-0.5 text-slate-500 font-medium">{{ $customer->created_at->translatedFormat('d F Y') }}</dd>
                </div>
            </dl>
        </div>

        <!-- RIGHT: RIWAYAT PESANAN & INQUIRY -->
        <div class="min-w-0 space-y-6 xl:col-span-2">
            <!-- RIWAYAT PESANAN TABLE -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-xs shrink-0">
                            <i class="fa-solid fa-shirt"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Riwayat Pesanan Pelanggan</h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">{{ $customer->orders->count() }} pesanan</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead>
                            <tr class="text-[11px] font-semibold text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <th class="py-3 px-5 font-normal uppercase tracking-wider">No. Order</th>
                                <th class="py-3 px-5 font-normal uppercase tracking-wider">Nama Pesanan</th>
                                <th class="py-3 px-5 font-normal uppercase tracking-wider text-right">Total Transaksi</th>
                                <th class="py-3 px-5 font-normal uppercase tracking-wider text-center">Pembayaran</th>
                                <th class="py-3 px-5 font-normal uppercase tracking-wider text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($customer->orders as $order)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-5 font-mono">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-brand-700 hover:underline">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-5 font-medium text-slate-800">
                                        {{ $order->name }}
                                    </td>
                                    <td class="py-3 px-5 text-right font-bold font-mono text-slate-900">
                                        {{ rupiah($order->grand_total) }}
                                    </td>
                                    <td class="py-3 px-5 text-center">
                                        <x-payment-badge :status="$order->payment_status" />
                                    </td>
                                    <td class="py-3 px-5 text-center">
                                        <x-order-status-badge :status="$order->status" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">
                                        Belum ada riwayat pesanan untuk pelanggan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- INQUIRY TERKAIT -->
            @if($customer->inquiries->isNotEmpty())
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">Inquiry Terkait</h3>
                    <ul class="divide-y divide-slate-100 text-xs">
                        @foreach($customer->inquiries as $inquiry)
                            <li class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                                <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="font-semibold text-slate-800 hover:text-brand-600 transition-colors flex items-center gap-2">
                                    <i class="fa-regular fa-comment-dots text-slate-400"></i>
                                    <span>#{{ $inquiry->id }} — {{ $inquiry->service_name ?: 'Konsultasi Umum' }}</span>
                                </a>
                                <span class="text-slate-400 font-mono">{{ $inquiry->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

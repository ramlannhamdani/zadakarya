@extends('layouts.admin')

@section('title', $customer->name)

@section('content')
<div class="grid gap-5 lg:grid-cols-3">
    <div class="admin-card">
        <div class="flex items-start justify-between">
            <h2 class="font-extrabold text-ink">Data Customer</h2>
            <a href="{{ route('admin.customers.edit', $customer) }}" class="text-sm font-semibold text-brand-600 hover:underline">Edit</a>
        </div>
        <dl class="mt-4 space-y-3 text-sm">
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Nama</dt><dd class="mt-0.5 font-medium text-ink">{{ $customer->name }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Perusahaan</dt><dd class="mt-0.5">{{ $customer->company ?? '—' }}</dd></div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">WhatsApp</dt>
                <dd class="mt-0.5 flex items-center gap-2">
                    {{ $customer->whatsapp ?? '—' }}
                    @if($customer->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $customer->whatsapp) }}" target="_blank" rel="noopener" class="rounded bg-[#25D366]/10 px-2 py-0.5 text-xs font-bold text-[#128C7E]">Chat</a>
                    @endif
                </dd>
            </div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Email</dt><dd class="mt-0.5">{{ $customer->email ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Alamat</dt><dd class="mt-0.5">{{ $customer->address ?? '—' }}{{ $customer->city ? ', '.$customer->city : '' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Catatan</dt><dd class="mt-0.5 whitespace-pre-line">{{ $customer->notes ?? '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-neutral-500">Terdaftar</dt><dd class="mt-0.5">{{ $customer->created_at->translatedFormat('d F Y') }}</dd></div>
        </dl>
    </div>

    <div class="lg:col-span-2 space-y-5">
        <div class="admin-card">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold text-ink">Riwayat Pesanan</h2>
                <a href="{{ route('admin.orders.create', ['customer' => $customer->id]) }}" class="btn-primary !px-4 !py-2 text-xs">+ Buat Pesanan</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                            <th class="pb-2.5 pr-4">No. Order</th>
                            <th class="pb-2.5 pr-4">Nama Pesanan</th>
                            <th class="pb-2.5 pr-4">Total</th>
                            <th class="pb-2.5 pr-4">Pembayaran</th>
                            <th class="pb-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($customer->orders as $order)
                            <tr>
                                <td class="py-3 pr-4"><a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-brand-600 hover:underline">{{ $order->order_number }}</a></td>
                                <td class="py-3 pr-4">{{ $order->name }}</td>
                                <td class="py-3 pr-4 font-semibold">{{ rupiah($order->grand_total) }}</td>
                                <td class="py-3 pr-4"><x-payment-badge :status="$order->payment_status" /></td>
                                <td class="py-3"><x-order-status-badge :status="$order->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-neutral-500">Belum ada pesanan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($customer->inquiries->isNotEmpty())
            <div class="admin-card">
                <h2 class="font-extrabold text-ink">Inquiry Terkait</h2>
                <ul class="mt-3 divide-y divide-line text-sm">
                    @foreach($customer->inquiries as $inquiry)
                        <li class="flex items-center justify-between py-2.5">
                            <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="font-medium hover:text-brand-600">#{{ $inquiry->id }} — {{ $inquiry->service_name ?: 'Umum' }}</a>
                            <span class="text-xs text-neutral-500">{{ $inquiry->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection

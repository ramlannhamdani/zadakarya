@extends('layouts.admin')

@section('title', 'Customer')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex min-w-0 flex-1 gap-2 sm:flex-none">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, perusahaan, WA..." class="form-input min-w-0 flex-1 sm:!w-64">
        <button type="submit" class="btn-outline !px-4 !py-2.5">Cari</button>
    </form>
    <a href="{{ route('admin.customers.create') }}" class="btn-primary">+ Customer Baru</a>
</div>

<div class="admin-card mt-5 overflow-x-auto !p-0">
    <table class="w-full min-w-[720px] text-sm">
        <thead>
            <tr class="border-b border-line bg-cream/60 text-left text-xs font-bold uppercase tracking-wider text-neutral-500">
                <th class="px-5 py-3">Nama</th>
                <th class="px-5 py-3">Perusahaan</th>
                <th class="px-5 py-3">WhatsApp</th>
                <th class="px-5 py-3">Kota</th>
                <th class="px-5 py-3 text-center">Pesanan</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($customers as $customer)
                <tr class="hover:bg-cream/40">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-ink hover:text-brand-600">{{ $customer->name }}</a>
                    </td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $customer->company ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $customer->whatsapp ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-neutral-600">{{ $customer->city ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-center font-semibold">{{ $customer->orders_count }}</td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('admin.customers.edit', $customer) }}" class="text-sm font-semibold text-brand-600 hover:underline">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-500">Belum ada customer.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $customers->links() }}</div>
@endsection

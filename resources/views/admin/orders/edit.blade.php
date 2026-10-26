@extends('layouts.admin')

@section('title', 'Edit Pesanan '.$order->order_number)

@section('content')
<div class="space-y-6">
    <!-- Header Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Detail Pesanan {{ $order->order_number }}</span>
        </a>
    </div>

    <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="w-full space-y-6">
        @csrf @method('PUT')
        @include('admin.orders._form', ['order' => $order])

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4">
            <p class="text-xs text-slate-400">Perubahan item tidak otomatis mengubah invoice yang sudah diterbitkan sebelumnya.</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.show', $order) }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs active:scale-95 transition-all">
                    <i class="fa-solid fa-check text-[11px]"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

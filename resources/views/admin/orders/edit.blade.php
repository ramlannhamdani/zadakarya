@extends('layouts.admin')

@section('title', 'Edit Pesanan '.$order->order_number)

@section('content')
<form method="POST" action="{{ route('admin.orders.update', $order) }}" class="max-w-5xl">
    @csrf @method('PUT')
    @include('admin.orders._form', ['order' => $order])

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary">Simpan Perubahan</button>
        <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline">Batal</a>
    </div>
</form>
@endsection

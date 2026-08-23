@extends('layouts.admin')

@section('title', 'Buat Pesanan')

@section('content')
<form method="POST" action="{{ route('admin.orders.store') }}" class="max-w-5xl">
    @csrf
    @include('admin.orders._form', ['order' => null])

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn-primary">Buat Pesanan</button>
        <a href="{{ route('admin.orders.index') }}" class="btn-outline">Batal</a>
        <p class="text-sm text-neutral-500">Nomor pesanan (ZDK-xxxx) dibuat otomatis oleh sistem.</p>
    </div>
</form>
@endsection

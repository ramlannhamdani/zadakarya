@extends('layouts.admin')

@section('title', $customer->exists ? 'Edit Customer' : 'Customer Baru')

@section('content')
<form method="POST" action="{{ $customer->exists ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" class="admin-card max-w-2xl">
    @csrf
    @if($customer->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="form-label" for="name">Nama <span class="text-brand-600">*</span></label>
            <input class="form-input" type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
        </div>
        <div>
            <label class="form-label" for="company">Perusahaan</label>
            <input class="form-input" type="text" id="company" name="company" value="{{ old('company', $customer->company) }}">
        </div>
        <div>
            <label class="form-label" for="whatsapp">WhatsApp</label>
            <input class="form-input" type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}" placeholder="+62 8xx-xxxx-xxxx">
        </div>
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-input" type="email" id="email" name="email" value="{{ old('email', $customer->email) }}">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label" for="address">Alamat</label>
            <textarea class="form-input" id="address" name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
        </div>
        <div>
            <label class="form-label" for="city">Kota</label>
            <input class="form-input" type="text" id="city" name="city" value="{{ old('city', $customer->city) }}">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label" for="notes">Catatan</label>
            <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes', $customer->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $customer->exists ? 'Simpan Perubahan' : 'Buat Customer' }}</button>
        <a href="{{ route('admin.customers.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
@endsection

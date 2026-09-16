@extends('layouts.admin')

@section('title', 'Integrasi Google Spreadsheet')

@section('content')
<div class="max-w-5xl space-y-6" x-data="spreadsheetManager()">

    {{-- Top Status Banner --}}
    <div class="admin-card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $isConnected ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H4.5A1.125 1.125 0 003.375 5.625m18.375 0V18.375M3.375 5.625v12.75m0-12.75h18.375M3.375 9.75h18.375M3.375 14.25h18.375M9.75 5.625v12.75M15.75 5.625v12.75" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold text-ink">Status Integrasi Google Sheets</h2>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isConnected ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $isConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                            {{ $isConnected ? 'Webhook Terhubung' : 'Belum Terhubung' }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Sinkronisasi otomatis database pesanan, rincian biaya HPP, dan riwayat pembayaran langsung ke Google Spreadsheet.
                    </p>
                    <div class="mt-2.5 flex flex-wrap items-center gap-4 text-xs text-neutral-600">
                        <span>Status Otomatis: <strong class="{{ $autoSync ? 'text-emerald-700' : 'text-neutral-500' }}">{{ $autoSync ? 'Aktif (Real-time)' : 'Manual' }}</strong></span>
                        @if($lastSyncedAt)
                            <span>Terakhir Sinkron: <strong class="text-ink">{{ \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() }}</strong></span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                @if($isConnected)
                    <button type="button" @click="testConnection()" :disabled="loading"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-cream disabled:opacity-50">
                        <svg class="h-4 w-4 text-neutral-500" :class="loading === 'test' && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Tes Koneksi</span>
                    </button>

                    <button type="button" @click="formatSheet()" :disabled="loading"
                            title="Format ulang struktur tab dan kartu dashboard laporan di Google Spreadsheet"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-cream disabled:opacity-50">
                        <svg class="h-4 w-4 text-indigo-600" :class="loading === 'format' && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        </svg>
                        <span>Tata Ulang Dashboard</span>
                    </button>

                    <button type="button" @click="syncAll()" :disabled="loading"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-50">
                        <svg class="h-4 w-4" :class="loading === 'sync' && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span>Sinkronkan Semua Data</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Live Notification Box --}}
        <div x-show="message.text" x-cloak class="mt-4 flex items-center justify-between rounded-lg p-3 text-xs font-semibold"
             :class="message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
            <div class="flex items-center gap-2">
                <span x-text="message.text"></span>
            </div>
            <button type="button" @click="message.text = ''" class="text-neutral-400 hover:text-neutral-700">✕</button>
        </div>

        {{-- Data summary chips --}}
        <div class="mt-4 grid grid-cols-3 gap-3 border-t border-line pt-4">
            <div class="rounded-lg bg-cream/60 p-3 text-center">
                <p class="text-[11px] font-semibold text-neutral-500">Data Pesanan</p>
                <p class="mt-0.5 text-lg font-extrabold text-ink">{{ number_format($orderCount) }}</p>
            </div>
            <div class="rounded-lg bg-cream/60 p-3 text-center">
                <p class="text-[11px] font-semibold text-neutral-500">Pengeluaran HPP</p>
                <p class="mt-0.5 text-lg font-extrabold text-ink">{{ number_format($costCount) }}</p>
            </div>
            <div class="rounded-lg bg-cream/60 p-3 text-center">
                <p class="text-[11px] font-semibold text-neutral-500">Data Pembayaran</p>
                <p class="mt-0.5 text-lg font-extrabold text-ink">{{ number_format($paymentCount) }}</p>
            </div>
        </div>
    </div>

    {{-- Form Setting Webhook URL --}}
    <div class="admin-card">
        <h3 class="font-extrabold text-ink">Pengaturan Webhook Google Sheets</h3>
        <p class="mt-1 text-xs text-neutral-500">
            Masukkan Webhook URL yang Anda dapatkan setelah menerapkan (deploy) script Apps Script di Google Sheets.
        </p>

        <form method="POST" action="{{ route('admin.spreadsheet.update') }}" class="mt-4 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="google_sheet_webhook_url" class="form-label">
                    URL Webhook Google Apps Script <span class="text-brand-600">*</span>
                </label>
                <div class="relative">
                    <input type="url" id="google_sheet_webhook_url" name="google_sheet_webhook_url"
                           value="{{ old('google_sheet_webhook_url', $webhookUrl) }}"
                           placeholder="https://script.google.com/macros/s/.../exec"
                           class="form-input font-mono text-xs pr-20" required>
                    <span class="pointer-events-none absolute right-3 top-2.5 text-[11px] font-mono text-neutral-400">/exec</span>
                </div>
                @error('google_sheet_webhook_url')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="google_sheet_auto_sync" name="google_sheet_auto_sync" value="1"
                       {{ old('google_sheet_auto_sync', $autoSync) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500">
                <label for="google_sheet_auto_sync" class="text-xs font-medium text-ink cursor-pointer select-none">
                    <strong>Sinkronkan Otomatis (Real-time)</strong> — Otomatis kirim atau update baris data ke Google Sheet setiap kali ada pesanan baru, penambahan biaya HPP, atau pembayaran masuk di web.
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    {{-- Interactive Step-by-Step Guide --}}
    <div class="admin-card">
        <div class="flex items-center justify-between border-b border-line pb-3">
            <div>
                <h3 class="font-extrabold text-ink">Panduan Setup Google Spreadsheet (Hanya 2 Menit)</h3>
                <p class="text-xs text-neutral-500">Ikuti 3 langkah mudah ini untuk mengaktifkan koneksi ke Google Spreadsheet Anda:</p>
            </div>
            <a href="https://sheets.new" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-cream px-3 py-1.5 text-xs font-bold text-ink transition hover:bg-white">
                <span>Buka Google Sheets Baru</span>
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            </a>
        </div>

        <div class="mt-4 space-y-4 text-xs text-ink">
            {{-- Step 1 --}}
            <div class="flex items-start gap-3 rounded-lg border border-line/60 bg-cream/40 p-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span>
                <div>
                    <h4 class="font-bold">Buka Google Sheets & Apps Script</h4>
                    <p class="mt-0.5 text-neutral-600">
                        Buka spreadsheet baru di Google Sheets. Di menu navigasi atas Google Sheets, klik menu <strong>Ekstensi (Extensions)</strong> &rarr; lalu klik <strong>Apps Script</strong>.
                    </p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="flex items-start gap-3 rounded-lg border border-line/60 bg-cream/40 p-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">2</span>
                <div class="w-full">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold">Salin dan Tempel Kode Otomasi Zada Karya</h4>
                        <button type="button" @click="copyCode()" class="inline-flex items-center gap-1 rounded bg-brand-600 px-2.5 py-1 text-[11px] font-bold text-white transition hover:bg-brand-700">
                            <span x-text="copied ? '✓ Berhasil Disalin!' : 'Salin Seluruh Kode Script'"></span>
                        </button>
                    </div>
                    <p class="mt-1 text-neutral-600">
                        Hapus semua teks default di halaman Apps Script, lalu tempel (paste) kode script di bawah ini. Simpan dengan menekan <strong>Ctrl + S</strong> (atau ikon disket).
                    </p>

                    <div class="mt-2.5 max-h-52 overflow-y-auto rounded-lg border border-slate-800 bg-slate-900 p-3 font-mono text-[11px] text-slate-200">
                        <pre><code>{{ $scriptCode }}</code></pre>
                    </div>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="flex items-start gap-3 rounded-lg border border-line/60 bg-cream/40 p-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">3</span>
                <div>
                    <h4 class="font-bold">Terapkan Sebagai Web App (Deploy) & Tempelkan URL di Sini</h4>
                    <p class="mt-0.5 text-neutral-600 leading-relaxed">
                        1. Di Apps Script, klik tombol biru <strong>Terapkan (Deploy)</strong> di pojok kanan atas &rarr; pilih <strong>Penerapan baru (New deployment)</strong>.<br>
                        2. Pada kolom jenis, pilih <strong>Aplikasi Web (Web app)</strong>.<br>
                        3. Pada opsi <em>Siapa yang memiliki akses (Who has access)</em>, pilih <strong>Siapa saja (Anyone)</strong>.<br>
                        4. Klik <strong>Terapkan (Deploy)</strong> dan berikan izin akses Google.<br>
                        5. Salin <strong>URL Aplikasi Web</strong> yang didapat, lalu tempelkan ke kolom <em>URL Webhook Google Apps Script</em> di atas & klik Simpan!
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function spreadsheetManager() {
    return {
        loading: null,
        copied: false,
        message: { text: '', type: 'success' },

        testConnection() {
            this.loading = 'test';
            this.message.text = '';
            fetch('{{ route("admin.spreadsheet.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.message = {
                    text: data.message,
                    type: data.success ? 'success' : 'error'
                };
            })
            .catch(err => {
                this.message = { text: 'Gagal melakukan tes koneksi: ' + err.message, type: 'error' };
            })
            .finally(() => { this.loading = null; });
        },

        formatSheet() {
            if (!confirm('Apakah Anda ingin menata ulang tab (Dashboard, Data_Pesanan, Data_Biaya_HPP, Data_Pembayaran) dan kartu laporan di Google Sheets sekarang?')) return;
            this.loading = 'format';
            this.message.text = '';
            fetch('{{ route("admin.spreadsheet.setup") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.message = {
                    text: data.message,
                    type: data.success ? 'success' : 'error'
                };
            })
            .catch(err => {
                this.message = { text: 'Gagal mengatur format sheet: ' + err.message, type: 'error' };
            })
            .finally(() => { this.loading = null; });
        },

        syncAll() {
            if (!confirm('Apakah Anda yakin ingin menyinkronkan seluruh data pesanan, pengeluaran HPP, dan pembayaran saat ini ke Google Spreadsheet?')) return;
            this.loading = 'sync';
            this.message.text = '';
            fetch('{{ route("admin.spreadsheet.sync") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.message = {
                    text: data.message + (data.counts ? ` (${data.counts.orders} pesanan, ${data.counts.costs} biaya HPP, ${data.counts.payments} pembayaran)` : ''),
                    type: data.success ? 'success' : 'error'
                };
            })
            .catch(err => {
                this.message = { text: 'Gagal menyinkronkan data: ' + err.message, type: 'error' };
            })
            .finally(() => { this.loading = null; });
        },

        copyCode() {
            const code = @js($scriptCode);
            navigator.clipboard.writeText(code).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            });
        }
    };
}
</script>
@endsection

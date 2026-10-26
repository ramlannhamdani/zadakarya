@extends('layouts.admin')

@section('title', 'Integrasi Google Spreadsheet')

@section('header-actions')
@if($isConnected)
<form method="POST" action="{{ route('admin.spreadsheet.sync') }}" class="inline">
    @csrf
    <button type="submit" class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs active:scale-95">
        <i class="fa-solid fa-arrows-rotate text-[11px]"></i>
        <span>Sinkronkan Sekarang</span>
    </button>
</form>
@endif
@endsection

@section('content')
<!-- 1. INTEGRATION STATUS & CONTROL HUB (Clean Flat Card) -->
<section class="bg-white rounded-2xl p-6 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
        
        <!-- Left Info & Status -->
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                <i class="fa-solid fa-file-excel"></i>
            </div>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Status Integrasi Google Sheets</h3>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ $isConnected ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'bg-amber-50 text-amber-700 font-semibold' }} text-[11px]">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        {{ $isConnected ? 'Webhook Terhubung' : 'Belum Terhubung' }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed max-w-2xl">
                    Sinkronisasi otomatis database pesanan, rincian biaya HPP, pengeluaran operasional (konveksi & pribadi), dan riwayat penerimaan kas langsung ke Google Spreadsheet.
                </p>
                <div class="flex flex-wrap items-center gap-4 pt-1 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="text-slate-400">Status Otomatis:</span>
                        <strong class="{{ $autoSync ? 'text-emerald-600 font-semibold' : 'text-slate-600' }}">
                            {{ $autoSync ? 'Aktif (Real-time)' : 'Manual' }}
                        </strong>
                    </span>
                    @if($lastSyncedAt)
                    <span class="text-slate-300">•</span>
                    <span class="flex items-center gap-1.5">
                        <span class="text-slate-400">Terakhir Sinkron:</span>
                        <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() }}</span>
                    </span>
                    @endif
                </div>

                @if($isConnected && $appScriptVersion && (! $liveScriptVersion || $liveScriptVersion !== $appScriptVersion))
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-900">
                    <p class="font-bold">
                        Skrip di Google masih versi {{ $liveScriptVersion ?: 'lama (belum terpasang v'.$appScriptVersion.')' }}, aplikasi sudah versi {{ $appScriptVersion }}.
                    </p>
                    <p class="mt-0.5 text-amber-800">
                        Pembaruan terpisah (Beban Konveksi vs Beban Pribadi) memerlukan deploy skrip baru di Google Apps Script. Salin kode terbaru di bawah dan lakukan deploy ulang (Deploy &rarr; Manage deployments &rarr; Edit &rarr; Version: New version &rarr; Deploy).
                    </p>
                </div>
                @endif

                @if($isConnected && ! $sheetUrl)
                <p class="mt-2.5 rounded-xl bg-slate-50 p-2.5 text-xs text-slate-500">
                    Tombol <strong>Buka Spreadsheet</strong> muncul setelah skrip versi {{ $appScriptVersion }} terpasang dan Anda menekan <strong>Tes Koneksi</strong> &mdash; alamat spreadsheet dilaporkan oleh skripnya, bukan tersimpan di URL webhook.
                </p>
                @endif

                @if($lastError)
                <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-900">
                    <p class="font-bold">Sinkronisasi terakhir gagal{{ $lastErrorAt ? ' '.\Carbon\Carbon::parse($lastErrorAt)->diffForHumans() : '' }}:</p>
                    <p class="mt-0.5 text-rose-800">{{ $lastError }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Right Action Buttons -->
        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 self-start lg:self-center shrink-0">
            @if($sheetUrl)
            <a href="{{ $sheetUrl }}" target="_blank" rel="noopener"
               title="{{ $sheetName ? 'Buka '.$sheetName : 'Buka spreadsheet yang terhubung' }}"
               class="px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-arrow-up-right-from-square text-slate-400 text-xs"></i>
                <span>Buka Spreadsheet{{ $sheetName ? ' ('.$sheetName.')' : '' }}</span>
            </a>
            @endif

            @if($isConnected)
            <form method="POST" action="{{ route('admin.spreadsheet.test') }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold flex items-center gap-2 transition-colors">
                    <i class="fa-solid fa-satellite-dish text-slate-400 text-xs"></i>
                    <span>Tes Koneksi</span>
                </button>
            </form>

            <form method="POST" action="{{ route('admin.spreadsheet.setup') }}" class="inline"
                  onsubmit="return confirm('Tata ulang struktur dashboard dan tab di Google Spreadsheet? Data yang ada akan tetap disinkronkan kembali.')">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <i class="fa-solid fa-wand-magic-sparkles text-slate-400 text-xs"></i>
                    <span>Tata Ulang</span>
                </button>
            </form>
            @endif
        </div>

    </div>

    <!-- 2. SYNCHRONIZED COUNTERS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 pt-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <!-- 1. Data Pesanan -->
        <div class="pt-4 sm:pt-0 sm:px-4 first:pt-0 first:pl-0">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Data Pesanan</span>
                <i class="fa-solid fa-box-open text-slate-300 text-xs"></i>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($orderCount) }}</span>
                <span class="text-[11px] text-slate-400">baris tersinkron</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Sheet: <span class="font-mono text-slate-600">DB_Pesanan</span></p>
        </div>

        <!-- 2. Pengeluaran HPP -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Pengeluaran HPP</span>
                <i class="fa-solid fa-tags text-slate-300 text-xs"></i>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($costCount) }}</span>
                <span class="text-[11px] text-slate-400">rincian bahan</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Sheet: <span class="font-mono text-slate-600">HPP_Produksi</span></p>
        </div>

        <!-- 3. Beban Operasional -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Beban Operasional</span>
                <i class="fa-solid fa-arrow-right-arrow-left text-slate-300 text-xs"></i>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($expenseCount ?? 0) }}</span>
                <span class="text-[11px] text-slate-400">transaksi kas</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Sheet: <span class="font-mono text-slate-600">Beban_Konveksi & Pribadi</span></p>
        </div>

        <!-- 4. Data Pembayaran -->
        <div class="pt-4 sm:pt-0 sm:px-4">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Data Pembayaran</span>
                <i class="fa-regular fa-credit-card text-slate-300 text-xs"></i>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900">{{ number_format($paymentCount) }}</span>
                <span class="text-[11px] text-slate-400">kas masuk</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">Sheet: <span class="font-mono text-slate-600">Kas_Masuk_Pelanggan</span></p>
        </div>
    </div>
</section>

<!-- 3. CONFIGURATION & LOGS GRID -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    
    <!-- LEFT: WEBHOOK & AUTOMATION SETTINGS (7 COLS) -->
    <div class="lg:col-span-7 space-y-6">
        <section class="bg-white rounded-2xl p-6 shadow-sm space-y-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 tracking-tight">Pengaturan Webhook Google Sheets</h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Simpan URL Webhook dari Deployment Google Apps Script. Setiap perubahan transaksi di workspace Zada Karya akan otomatis dikirim ke Google Sheets.
                </p>
            </div>

            <!-- Form Webhook -->
            <form method="POST" action="{{ route('admin.spreadsheet.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="google_sheet_webhook_url" class="text-xs font-semibold text-slate-700 block mb-1.5">
                        URL Webhook Google Apps Script <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="url" 
                        id="google_sheet_webhook_url" 
                        name="google_sheet_webhook_url"
                        value="{{ old('google_sheet_webhook_url', $webhookUrl) }}" 
                        placeholder="https://script.google.com/macros/s/.../exec"
                        class="w-full bg-slate-50 text-xs font-mono rounded-xl px-3.5 py-2.5 text-slate-700 focus:outline-none focus:ring-1 focus:ring-slate-300"
                        required
                    />
                    <span class="text-[10px] text-slate-400 block mt-1">Harus berakhiran <code class="font-mono text-slate-600">/exec</code> dengan hak akses "Anyone" (Siapa saja).</span>
                </div>

                <!-- Checkbox Sinkronkan Otomatis -->
                <div class="p-3.5 rounded-xl bg-slate-50 flex items-start gap-3">
                    <input 
                        type="checkbox" 
                        id="google_sheet_auto_sync" 
                        name="google_sheet_auto_sync"
                        value="1"
                        {{ old('google_sheet_auto_sync', $autoSync) ? 'checked' : '' }}
                        class="mt-0.5 rounded text-slate-900 focus:ring-0 cursor-pointer w-4 h-4"
                    />
                    <label for="google_sheet_auto_sync" class="text-xs cursor-pointer select-none">
                        <span class="font-bold text-slate-800 block">Sinkronkan Otomatis (Real-time)</span>
                        <p class="text-slate-500 text-[11px] mt-0.5 leading-relaxed">
                            Otomatis kirim atau perbarui data ke Google Sheet saat pesanan baru dibuat, rincian biaya HPP dicatat, atau pembayaran masuk diterima.
                        </p>
                    </label>
                </div>

                <!-- Button Save -->
                <div class="pt-2">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-all shadow-xs active:scale-95 flex items-center gap-2">
                        <i class="fa-regular fa-floppy-disk text-xs"></i>
                        <span>Simpan Pengaturan & Sinkronkan</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Structure Mapping Card -->
        <section class="bg-white rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Struktur Sheet Database yang Didukung</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Sistem memetakan data secara otomatis ke lembar kerja Google Spreadsheet.</p>
                </div>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">v{{ $appScriptVersion ?? '2.4' }} Auto-Mapping</span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50/70 space-y-1">
                    <div class="font-mono font-bold text-slate-800 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>1. DB_Pesanan</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Order ID, Customer, Produk, Qty, Total, Sisa Bayar, Deadline, Status.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-50/70 space-y-1">
                    <div class="font-mono font-bold text-slate-800 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span>2. HPP_Produksi</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Belanja kain, sablon, bordir, makloon, benang, dan aksesoris pesanan.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-50/70 space-y-1">
                    <div class="font-mono font-bold text-slate-800 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>3. Beban_Konveksi</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Overhead konveksi: listrik, sewa workshop, gaji staf, pemeliharaan mesin.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-50/70 space-y-1">
                    <div class="font-mono font-bold text-slate-800 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <span>4. Kas_Masuk</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Catatan pembayaran customer, tanggal setor, metode transfer, dan referensi.</p>
                </div>
            </div>
        </section>
    </div>

    <!-- RIGHT: SCRIPT CODE & INSTRUCTIONS (5 COLS) -->
    <div class="lg:col-span-5 space-y-6" x-data="{ showCode: false, copied: false }">
        <section class="bg-white rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Kode Google Apps Script</h4>
                    <p class="text-[11px] text-slate-400">Master template versi {{ $appScriptVersion ?? '2.4' }}</p>
                </div>
                <button type="button" @click="navigator.clipboard.writeText(@js($scriptCode)).then(() => { copied = true; setTimeout(() => copied = false, 2500); })"
                        class="px-3 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs flex items-center gap-1.5 transition-colors">
                    <i class="fa-regular fa-copy text-xs"></i>
                    <span x-text="copied ? 'Disalin!' : 'Salin Kode'"></span>
                </button>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">
                Salin seluruh kode script di bawah ke Google Apps Script (menu <em>Extensions &rarr; Apps Script</em> di spreadsheet Anda). Kemudian deploy sebagai Web App dengan hak akses <strong>Anyone</strong>.
            </p>

            <div class="relative">
                <button type="button" @click="showCode = !showCode"
                        class="w-full py-2 px-3 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium text-xs flex items-center justify-between transition-colors">
                    <span x-text="showCode ? 'Sembunyikan Cuplikan Kode' : 'Lihat Cuplikan Kode Script'"></span>
                    <i class="fa-solid" :class="showCode ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div x-show="showCode" x-cloak class="mt-2.5 max-h-80 overflow-y-auto rounded-xl bg-slate-900 p-3.5 font-mono text-[11px] text-slate-200">
                    <pre><code>{{ $scriptCode }}</code></pre>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-emerald-50/70 text-xs text-emerald-800 space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Panduan Setup Google Spreadsheet &amp; Deployment:</span>
                </div>
                <ol class="list-decimal pl-5 space-y-1 text-[11px] text-emerald-900">
                    <li>Buka Spreadsheet Google Anda.</li>
                    <li>Pilih <strong>Extensions &rarr; Apps Script</strong>.</li>
                    <li>Ganti seluruh isi <code>Code.gs</code> dengan kode di atas.</li>
                    <li>Klik <strong>Deploy &rarr; New deployment &rarr; Web app</strong>.</li>
                    <li>Set <em>Execute as: Me</em>, <em>Who has access: Anyone</em>.</li>
                    <li>Salin URL Web App dan simpan di kolom webhook di sebelah kiri.</li>
                </ol>
            </div>
        </section>
    </div>

</div>
@endsection

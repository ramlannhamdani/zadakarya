# Panduan Agen — Zada Karya Production

Company profile + panel admin (pesanan, invoice, pembayaran) + halaman lacak pesanan publik.
Laravel 13, PHP 8.3, Blade + Tailwind v4 + Alpine.js 3, dompdf, Intervention Image.

Dokumen lain: `README.md` (gambaran fitur), `DEPLOYMENT.md` (deploy ke cPanel, lengkap).
Berkas ini khusus berisi hal yang **tidak kelihatan dari membaca kode** — sebagian besar
didapat dari kesalahan yang sudah pernah terjadi. Baca sebelum menyentuh kode.

---

## Lingkungan lokal (Windows)

**PHP dan Composer tidak ada di PATH.** Pakai Laragon:

```
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
C:\laragon\bin\composer
```

Database dev memakai **SQLite** (`database/database.sqlite`), bukan MySQL Laragon.
Production memakai MySQL di cPanel.

Login admin hasil seed: `admin@zadakarya.id` / `zadakarya123` (dari `ADMIN_*` di `.env`).
Data demo (pesanan `ZDK-0001`) hanya di-seed saat `APP_ENV=local` lewat `DemoSeeder`.

## Perintah

```bash
php artisan test --compact     # 84 tes; harus hijau sebelum commit
npm run build                  # WAJIB kalau resources/js atau resources/css berubah
```

`public/build` **ikut di-commit** karena shared hosting tidak punya Node.js. Kalau lupa
`npm run build`, perubahan CSS/JS tidak akan pernah sampai ke production meski kode benar.

Deploy (detail di `DEPLOYMENT.md`):

```bash
cd ~/zadakarya && git pull origin main
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`route:cache` gampang terlupa dan gejalanya menyesatkan — halaman jadi **404**, bukan error.
Kalau menambah atau mengubah rute, sebutkan perintah ini saat menyerahkan hasil.

---

## Konvensi

- **Semua teks yang dilihat pengguna berbahasa Indonesia**, termasuk slug URL:
  `/layanan`, `/galeri`, `/portfolio`, `/tentang-kami`, `/kontak`, `/konsultasi`,
  `/lacak-pesanan`. Komentar di kode juga bahasa Indonesia.
- **Jangan pernah menulis path secara harfiah.** Selalu `route('nama.rute')`, termasuk di
  tes. Slug pernah berubah (`/tracking` → `/lacak-pesanan`); yang memakai nama rute
  ikut sendiri, yang hardcoded ikut rusak.
- Komentar menjelaskan **kenapa**, bukan apa. Kode yang jelas tidak perlu diberi komentar.

## Aturan domain

- Nomor pesanan `ZDK-XXXX-DDMMYY`, dijamin unik lewat `App\Support\Sequence`
  (tabel `number_sequences`), bukan `count()+1`.
- **Nomor invoice = nomor pesanan.** Invoice kedua dst. untuk pesanan yang sama
  diberi akhiran `-2`, `-3` (`Invoice::nextNumberFor`).
- Pesanan punya **7 tahap** yang terkunci di `App\Support\Stages`.
- Pembayaran **tidak boleh tercampur antar invoice maupun antar pesanan** milik customer
  yang sama. Kalau satu pesanan punya >1 invoice, hitung dari `payments` yang
  `invoice_id`-nya cocok — bukan `$order->amount_paid`.
- PDF invoice dibuat lewat `App\Support\InvoicePdf` supaya berkas yang diunduh admin,
  yang tampil di pratinjau, dan yang dibagikan ke customer identik.
- **DP vs pelunasan dibaca dari kolom `payments.type`**, tidak pernah ditebak dari
  catatan. Seluruh laporan DP, arus kas, dan piutang bergantung padanya.
- Setiap baris yang dikirim ke spreadsheet membawa id (`ORD-`, `PAY-`, `CST-`) di kolom
  tersembunyi, supaya baris yang datanya dihapus di panel bisa ikut dihapus di sheet.
  Menghapus data tanpa mengirim `action: delete` membuat laporan melebih-lebihkan omzet.

---

## Jebakan yang sudah pernah menggigit

**Alpine: `x-show` tidak menghentikan input terkirim.** `x-show` hanya memasang
`display:none`; input di dalamnya tetap ikut ter-submit. Untuk input form yang kondisional
pakai `<template x-if>`. Kesalahan ini pernah **menghapus seluruh gambar** yang tersimpan
(logo, tanda tangan, stempel) setiap kali form Pengaturan disimpan.

**dompdf hanya menghitung `position: absolute` untuk anak langsung `<body>`.** Di dalam
`<td>` atau div bersarang posisinya diabaikan. Tanda tangan dan stempel di
`resources/views/admin/invoices/pdf.blade.php` karena itu berada di level `<body>` dengan
koordinat yang dihitung di PHP. dompdf juga mengabaikan margin `@page` (pakai padding
`.sheet`) dan tidak bisa membaca `.ico`.

**Blade meng-escape `@section('judul', $isi)`.** Argumen kedua dilewatkan `e()`. Kalau
layout mencetaknya lagi dengan `{{ }}`, hasilnya ganda: `&` jadi `&amp;`. Lihat
`resources/views/layouts/site.blade.php`. Di atribut komponen tulis `&` apa adanya,
jangan `&amp;`.

**`route:cache` menolak closure.** Rute redirect harus lewat controller atau
`Route::permanentRedirect`, tidak boleh `Route::get('/x', fn () => ...)`.

**`Route::permanentRedirect` membuang query string.** Untuk alamat yang membawa parameter
(mis. `/tracking?order=...` yang sudah terkirim ke customer) pakai controller yang
meneruskan `$request->query()`.

**URL bertanda tangan terikat host.** Tautan invoice yang dibuat di `buatseragam.com`
ditolak di `www.buatseragam.com`. Keduanya saat ini sama-sama menyajikan situs.

**Tailwind `line-clamp-N` butuh `display:-webkit-box`.** Menggabungkannya dengan `block`
membuatnya mati diam-diam.

**Mengubah `resources/views/admin/spreadsheet/code.js` tidak mengubah apa pun di akun
Google.** Skripnya harus ditempel ulang ke Apps Script lalu di-*Deploy* sebagai versi baru.
Berkas itu punya `SCRIPT_VERSION` yang ikut di setiap respons; panel admin
membandingkannya dengan versi di aplikasi dan memperingatkan kalau tertinggal. Naikkan
nilainya setiap kali berkas itu diubah, kalau tidak peringatannya tidak berguna.

**Di sel gabungan, nilainya hanya ada di sel kiri-atas.** Kartu KPI dashboard
menggabung dua kolom (A:B, C:D, …), jadi rumus yang menunjuk kolom kanan membaca sel
kosong dan menghasilkan 0 tanpa error. Pernah membuat Margin Keuntungan dan % Porsi
total tampil 0,0% padahal datanya ada.

**Label kategori HPP di `code.js` harus sama persis dengan `OrderCost::CATEGORIES`.**
SUMIF mencocokkan teks utuh; selisih satu kata membuat kategori itu diam-diam Rp0.
Dijaga oleh `SpreadsheetScriptTest`.

**Grafik Apps Script melayang di atas sel.** Sediakan baris kosong setinggi grafiknya,
kalau tidak ia menutupi isi di bawahnya.

**Grafik arus kas dipasang MANUAL, jangan coba dibuat dari kode lagi.** Pembuatan lewat
`EmbeddedChartBuilder` dicoba berkali-kali (label bulan teks, `SpreadsheetApp.flush()`,
`setNumHeaders(1)`, `useFirstColumnAsDomain`, opsi diminimalkan, dibuat setelah data
masuk) dan semuanya menghasilkan kotak berisi judul saja — tanpa sumbu, tanpa legenda.
Grafik yang sama dibuat lewat menu Insert > Chart langsung berhasil. Karena Apps Script
tidak bisa dijalankan di lingkungan pengembangan, ini berhenti sebagai keputusan sadar,
bukan kegagalan yang belum selesai. `buildCashChart()` sekarang hanya menampilkan
petunjuk pemasangan selagi grafiknya belum ada.

**Tidak boleh ada `removeChart` di mana pun.** Grafiknya buatan tangan; sekali terhapus
harus dipasang ulang manual. Dijaga oleh `SpreadsheetScriptTest`.

**Di Apps Script, sel berisi rumus terhitung "ada isinya" oleh `getLastRow()`.**
Pernah membuat rumus Saldo dipra-isi sampai baris 500, sehingga `appendRow` mendarat di
baris 501. Rumus ditulis bersama barisnya, jangan dipra-isi.

**Format sheet jangan dibatasi nomor baris** (`A4:A300`). Begitu data melewatinya, sheet
mendadak polos. Pakai `dataRowCount(sheet)` dan `applyRowStripes()`.

**`Http::fake()` menggabungkan stub, tidak menggantikannya**, dan stub pertama yang cocok
menang. Memasang fake di `setUp` membuat tes yang butuh respons gagal tidak akan pernah
mendapatkannya. Pasang per tes, atau pakai satu closure yang isinya bisa diubah.

---

## Cara memverifikasi

Jangan menyerahkan perubahan tampilan hanya berbekal "tes hijau". Yang dipakai selama ini:
render halaman jadi HTML statis (asset ditulis ulang ke `file://`), lalu screenshot dengan
Edge headless `--headless=new --virtual-time-budget=...` pada lebar 1440 / 1280 / 1024 /
390. Panel admin paling sering dibuka di **tablet landscape**, jadi 1024 wajib dicek.

Catatan: `--virtual-time-budget` membekukan transisi CSS, sehingga animasi scroll-reveal
tampak belum muncul di screenshot — itu artefak alat, bukan bug.

Untuk PDF, baca kembali berkas hasilnya secara visual; jangan berasumsi dari HTML-nya.

 # Zada Karya Production

Website company profile + sistem manajemen pesanan & tracking produksi untuk **Zada Karya Production** (jasa konveksi / garment custom). Dibangun sesuai PRD v1.0.

## Fitur

**Website Publik**
- Homepage marketing (hero, layanan, why-us, proses produksi, portfolio, blog, CTA WhatsApp)
- Halaman Layanan (`/layanan`, `/layanan/{slug}`) — dikelola dari admin
- Portfolio dengan filter kategori (`/portfolio`, `/portfolio/{slug}`) + carousel ulasan bergaya Google Maps di bawah grid
- Blog dengan kategori, pencarian, dan tag (`/blog`, `/blog/{slug}`)
- Tentang Kami, Kontak, Form Konsultasi (tersimpan sebagai inquiry + diarahkan ke WhatsApp)
- **Tracking pesanan tanpa login** (`/tracking`) — masukkan nomor lengkap (format `ZDK-XXXX-HHMMTT`), tampil timeline 7 tahap (hijau selesai / kuning diproses / abu menunggu) + foto produksi yang di-publish admin
- SEO: meta title/description, Open Graph, canonical, `sitemap.xml`, `robots.txt`
- Event Google Analytics: `whatsapp_click`, `consultation_submit`, `tracking_search`

**Admin Panel** (`/admin`)
- Dashboard berorientasi tindakan (ringkasan, "perlu tindakan", pesanan & inquiry terbaru)
- Customer, Inquiry (ubah status, konversi ke customer)
- Pesanan: nomor **ZDK-XXXX-HHMMTT** otomatis (inti berurutan + akhiran tanggal-bulan-tahun pembuatan, contoh `ZDK-0012-140226`), multi-item, deadline, file attachment (internal), catatan internal, riwayat aktivitas
- Tracking 7 tahap: Mulai / Selesaikan / Buka Kembali — tahap berikutnya otomatis berjalan
- Foto produksi per tahap dengan visibilitas **Internal (default) / Public**
- Invoice bernomor **sama dengan nomor pesanan** (invoice tambahan: akhiran -2, -3), preview + **download PDF** landscape ber-branding
- Pembayaran DP & pelunasan — status Belum Dibayar / DP / Lunas dihitung otomatis
- CMS: Layanan, Portfolio (+kategori, galeri, thumbnail otomatis), Blog (+kategori, draft/publish), Ulasan Google (disalin admin dari Google Maps), Pengaturan (kontak, logo, link Google Maps, SEO default, info rekening invoice)

## Tech Stack

Laravel 13 · Blade · Tailwind CSS v4 · Alpine.js · SQLite (dev) / MySQL (production) · dompdf · Intervention Image (thumbnail WebP otomatis)

## Menjalankan Secara Lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed     # membuat admin + data awal (+ data demo di env local)
php artisan storage:link
npm install && npm run build
php artisan serve
```

- Website: http://localhost:8000
- Admin: http://localhost:8000/admin
- **Login default:** `admin@zadakarya.id` / `zadakarya123` (atur via `ADMIN_EMAIL` / `ADMIN_PASSWORD` di `.env` sebelum seeding — **wajib ganti di production**)
- Demo (hanya env `local`): satu pesanan demo di tahap 4 (nomor lihat di admin) dengan DP tercatat — coba di `/tracking`

Menjalankan test:

```bash
php artisan test
```

## Deploy ke Shared Hosting / cPanel

1. **Database**: buat database MySQL + user di cPanel, isi `DB_*` di `.env` (`DB_CONNECTION=mysql`).
2. **Upload kode** (Git via cPanel atau upload ZIP) ke folder di luar `public_html`, contoh `~/zadakarya`.
3. **Composer**: `composer install --no-dev --optimize-autoloader` (via SSH/Terminal cPanel).
4. **Assets**: jalankan `npm run build` di lokal, lalu upload folder `public/build` (shared hosting tidak butuh Node).
5. **Document root**: arahkan domain ke `~/zadakarya/public`. Jika tidak bisa, salin isi `public/` ke `public_html` dan sesuaikan path di `index.php` (`require __DIR__.'/../zadakarya/vendor/autoload.php'` dan `bootstrap/app.php`).
6. **.env production**:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainanda.com
   ADMIN_PASSWORD=<password-kuat>
   ```
7. Jalankan:
   ```bash
   php artisan key:generate
   php artisan migrate --seed --force
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
8. **HTTPS**: aktifkan AutoSSL di cPanel.

> Catatan: seeder data demo hanya berjalan pada `APP_ENV=local`, jadi production dimulai bersih (admin + pengaturan + layanan + kategori saja).

## Keputusan Produk yang Dikunci (dari PRD)

- Order hanya dibuat admin setelah deal via WhatsApp — tidak ada checkout customer.
- Nomor order `ZDK-XXXX-HHMMTT` otomatis (inti sequential + akhiran tanggal-bulan-tahun, mis. 14 Feb 2026 → `140226`), tidak bisa diketik manual, dipakai sebagai nomor tracking.
- Nomor invoice mengikuti nomor pesanan (`ZDK-0012-140226`); invoice tambahan untuk pesanan yang sama diberi akhiran `-2`, `-3`.
- Tracking publik tepat 7 tahap dan tidak pernah menampilkan data sensitif (catatan internal, file internal, foto internal, margin).
- Foto produksi default **Internal**; admin harus eksplisit menjadikannya Public.

## Struktur Penting

```
app/Support/Sequence.php      # Nomor ZDK-/INV- atomik (lock + transaksi)
app/Support/Stages.php        # Definisi 7 tahap tracking (terkunci)
app/Support/ImageUploader.php # Resize + thumbnail WebP otomatis
app/Http/Controllers/Site     # Controller website publik
app/Http/Controllers/Admin    # Controller panel admin
resources/views/site          # Blade website publik
resources/views/admin         # Blade panel admin (termasuk template PDF invoice)
```

# Panduan Deploy ke Shared Hosting (cPanel) via Terminal

Panduan ini untuk menaikkan aplikasi dari GitHub (`ramlannhamdani/zadakarya`) ke shared hosting menggunakan **Terminal** bawaan cPanel (atau SSH).

> Asset CSS/JS sudah di-build dan ikut ter-commit di `public/build` — **tidak perlu Node.js/npm di hosting**.

---

## 0. Persiapan di cPanel (sekali saja)

1. **PHP 8.3+** — menu *Select PHP Version* (atau *MultiPHP Manager*), pilih PHP 8.3, aktifkan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `curl`, `intl`, `exif`.
2. **Database** — menu *MySQL Databases*:
   - Buat database, contoh: `namacpanel_zadakarya`
   - Buat user + password kuat, lalu **Add User To Database** dengan centang *All Privileges*.
   - Catat: nama database, username, password.
3. Buka menu **Terminal** di cPanel (jika tidak ada, minta akses SSH ke penyedia hosting).

Cek dulu versi PHP CLI di terminal:

```bash
php -v
```

Jika bukan 8.3, biasanya binary spesifik tersedia — pakai path ini di semua perintah `php`:

```bash
/usr/local/bin/ea-php83 -v
# atau
which ea-php83
```

---

## 1. Clone dari GitHub

```bash
cd ~
git clone https://github.com/ramlannhamdani/zadakarya.git
cd zadakarya
```

> Jika repo dibuat **private**: buat *Personal Access Token* (GitHub → Settings → Developer settings → Fine-grained token, akses repo ini, permission Contents: Read), lalu clone dengan:
> `git clone https://<TOKEN>@github.com/ramlannhamdani/zadakarya.git`

## 2. Install dependency PHP

```bash
composer install --no-dev --optimize-autoloader
```

Jika `composer` tidak tersedia di hosting:

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

## 3. Konfigurasi `.env`

```bash
cp .env.example .env
nano .env
```

Ubah nilai-nilai berikut (simpan dengan `Ctrl+O`, keluar `Ctrl+X`):

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainanda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=namacpanel_zadakarya
DB_USERNAME=namacpanel_user
DB_PASSWORD=password-database

QUEUE_CONNECTION=sync

ADMIN_NAME="Admin Zada Karya"
ADMIN_EMAIL=admin@zadakarya.id
ADMIN_PASSWORD=GANTI-dengan-password-kuat
```

> `ADMIN_PASSWORD` dipakai saat seeding untuk membuat akun login admin — **wajib diganti**, jangan pakai default.

## 4. Setup aplikasi

```bash
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
```

`--seed` membuat: akun admin, pengaturan perusahaan, 8 layanan, dan kategori. Data demo TIDAK ikut karena `APP_ENV=production`.

## 5. Arahkan domain ke folder `public`

**Cara A (disarankan)** — jika domain adalah addon/sub-domain, atau cPanel mengizinkan ubah document root: menu *Domains* → *Manage* → ubah **Document Root** menjadi:

```text
/home/namacpanel/zadakarya/public
```

**Cara B** — jika domain utama dan document root `public_html` tidak bisa diubah, ganti `public_html` dengan symlink. Jika `public_html` masih **kosong** (hosting baru):

```bash
cd ~
rmdir public_html
ln -s ~/zadakarya/public ~/public_html
```

Jika `rmdir` gagal karena ada file bawaan hosting (mis. `.htaccess` tersembunyi), pakai:

```bash
cd ~
mv public_html public_html_backup
ln -s ~/zadakarya/public ~/public_html
```

Verifikasi symlink terbentuk lalu buka website:

```bash
ls -la ~ | grep public_html
# harus tampil: public_html -> /home/namacpanel/zadakarya/public
```

> Jika hosting menolak symlink untuk `public_html`, gunakan **Cara C**: salin isi `~/zadakarya/public/*` (termasuk `.htaccess`) ke `public_html`, lalu edit `public_html/index.php` — ganti dua path:
> ```php
> require __DIR__.'/../zadakarya/vendor/autoload.php';
> $app = require_once __DIR__.'/../zadakarya/bootstrap/app.php';
> ```
> dan buat ulang link storage: `ln -s ~/zadakarya/storage/app/public ~/public_html/storage`

## 6. Optimasi & HTTPS

```bash
cd ~/zadakarya
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Aktifkan SSL: cPanel → *SSL/TLS Status* → jalankan **AutoSSL** untuk domain, lalu pastikan `APP_URL` memakai `https://`.

## 7. Verifikasi

- Buka `https://domainanda.com` → homepage tampil.
- `https://domainanda.com/admin` → login dengan `ADMIN_EMAIL` / `ADMIN_PASSWORD` dari `.env`.
- Buat 1 pesanan uji dari admin → cek nomor `ZDK-0001` → cari di `/tracking`.

---

## Update Rutin (setiap ada perubahan kode)

Alur kerja: ubah kode di lokal → `npm run build` (jika ada perubahan CSS/JS) → commit & push ke GitHub → lalu di terminal hosting:

```bash
cd ~/zadakarya
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Website hanya *maintenance mode* beberapa detik selama proses ini.

## Troubleshooting Cepat

| Gejala | Solusi |
|---|---|
| Error 500 setelah deploy | Cek `storage/logs/laravel.log`; pastikan `php artisan key:generate` sudah jalan dan `.env` benar |
| Halaman tampil tanpa CSS | Pastikan folder `public/build` ikut ter-pull dan document root mengarah ke `public` |
| Gambar upload tidak muncul | Jalankan ulang `php artisan storage:link` (atau link manual pada Cara C) |
| `composer install` kehabisan memori | `php -d memory_limit=-1 composer.phar install --no-dev` |
| Perubahan `.env` tidak terasa | Jalankan `php artisan config:cache` lagi |
| Error hak akses folder | `chmod -R 775 storage bootstrap/cache` |

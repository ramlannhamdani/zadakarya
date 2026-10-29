# Keselarasan dashboard admin dan spreadsheet

Versi Apps Script: `2026-10-29.02`.

Dashboard spreadsheet memuat 16 kartu dengan urutan yang sama seperti admin. Rincian
di bawahnya terdiri dari piutang kritis, WIP, komposisi HPP, prive bulan berjalan,
tren kas, dan tren laba. Batas daftar sama: enam pesanan dan tujuh transaksi prive.
Total mencakup seluruh data, bukan hanya baris yang tampil.

Aturan yang disamakan:

- Pesanan dibatalkan dikeluarkan dari omzet, piutang, dan HPP untuk laba. Mutasi kas
  yang benar-benar sudah terjadi tetap masuk saldo serta tren kas.
- Piutang kritis mencakup pesanan selesai atau deadline sampai hari ini; piutang
  berjalan tidak menghitung ulang piutang kritis.
- Kategori HPP mengikuti model, termasuk penggabungan kategori lama ke Biaya Lainnya.
- Prive diambil dari bulan berjalan dan dipisahkan dari laba operasional.
- Tren memakai delapan bulan sampai bulan berjalan, termasuk saat berganti tahun.
  Pinjaman/cicilan masuk arus kas; laba memakai nilai order, HPP, dan beban konveksi.
- Aset memakai nilai buku tanpa mengalikan ulang kuantitas, sesuai dashboard admin.
- Progres produksi berasal dari tahap yang benar-benar selesai.
- DP vs HPP memakai pembayaran bertipe DP; pelunasan tetap terpisah.

Dashboard disegarkan setelah sinkronisasi penuh, perubahan, atau penghapusan via
webhook. Membuka spreadsheet atau memilih **Zada Karya > Segarkan Dashboard** juga
menghitung ulang periode. Jika sheet dibiarkan terbuka melewati pergantian hari/bulan
tanpa transaksi, gunakan menu tersebut. Perubahan manual pada tab sumber memicu
penyegaran lewat `onEdit`.

## Pembaruan di Google

1. Deploy kode aplikasi dan aset build ke server sesuai DEPLOYMENT.md.
2. Salin seluruh kode terbaru dari panel Spreadsheet ke Apps Script.
3. Edit deployment yang sama, pilih New version, lalu Deploy. URL webhook tetap.
4. Tes Koneksi dari panel; pastikan versi `2026-10-29.02`.
5. Tekan **Tata Ulang** di panel. Panel menata ulang lalu mengisi ulang semua data.
   Jangan hanya menjalankan setupSheet di editor, karena setup mengosongkan tab sumber.

Grafik dipasang manual sesuai panduan proyek. Grafik yang sudah ada dipertahankan
ID-nya, dipindahkan ke area yang disediakan, dan diarahkan ke seri delapan bulan pada
tab **Tren Dashboard**. Bila belum ada:

- Kas: pilih A3:C11 di Tren Dashboard, Insert > Chart, pilih Column chart.
- Laba: pilih E3:G11, Insert > Chart, pilih Line chart.
- Pindahkan grafik ke tab Dashboard: kas di kolom A, laba di kolom G. Pilih menu
  Segarkan Dashboard untuk menyesuaikan sumber dan posisinya.

## Verifikasi

`php artisan test --compact` menjalankan uji Laravel dan uji kesamaan menggunakan
Node.js yang tersedia di PATH. Uji membaca skrip produksi yang sama, menjalankan
webhook pada mock spreadsheet, lalu membandingkan seluruh metrik, daftar, dan tren
terhadap respons dashboard admin. Pengujian mencakup sync_all, perubahan sebagian,
penghapusan, nilai rugi, pergantian tahun, dan identitas grafik manual setelah penataan
ulang. Mock tidak menjalankan mesin formula atau tampilan Google Sheets; verifikasi
langsung setelah deploy tetap diperlukan.

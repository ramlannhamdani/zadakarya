<?php

namespace Tests\Feature;

use App\Models\OrderCost;
use App\Support\GoogleAppsScriptCode;
use Tests\TestCase;

/**
 * Skrip Apps Script tidak bisa dijalankan dari sini, tapi kesalahan yang paling
 * mahal di sana bersifat diam: rumus yang salah alamat atau label yang meleset
 * menghasilkan Rp0, bukan error. Yang dijaga di sini adalah hal-hal itu.
 */
class SpreadsheetScriptTest extends TestCase
{
    private string $script;

    protected function setUp(): void
    {
        parent::setUp();

        $this->script = GoogleAppsScriptCode::getScript();
    }

    public function test_hpp_categories_come_from_the_data_and_are_ranked_by_size(): void
    {
        // Dulu dashboard memakai daftar kategori tetap di dalam kode dan
        // mencocokkannya dengan SUMIF. Begitu label Laravel berbunyi "Jasa
        // Makloon (Bordir/Sablon)" sementara kode menulis "Jasa Makloon",
        // kategori itu diam-diam tampil Rp0. Dibaca dari datanya, label tidak
        // mungkin meleset lagi — dan sekaligus bisa diurutkan menurut besarnya.
        $this->assertStringNotContainsString('var cats = [', $this->script);

        preg_match('/getRange\(\'A11\'\)\.setFormula\((.*?)\);/s', $this->script, $m);
        $this->assertNotEmpty($m, 'Rumus komposisi HPP tidak ditemukan.');

        $formula = $m[1];
        $this->assertStringContainsString('Rincian Biaya HPP', $formula);
        $this->assertStringContainsString('group by C', $formula);
        $this->assertStringContainsString('order by sum(H) desc', $formula);
        $this->assertStringContainsString('limit 5', $formula);

        // Batasnya harus menampung seluruh kategori yang mungkin dikirim Laravel,
        // kalau tidak kategori paling kecil hilang dari laporan.
        $this->assertGreaterThanOrEqual(count(OrderCost::CATEGORIES), 5);
    }

    public function test_orders_are_ranked_by_margin_from_largest(): void
    {
        preg_match('/getRange\(\'A20\'\)\.setFormula\((.*?)\);/s', $this->script, $m);
        $this->assertNotEmpty($m, 'Rumus peringkat margin tidak ditemukan.');

        $formula = $m[1];
        $this->assertStringContainsString('Data Order', $formula);
        $this->assertStringContainsString('order by K/G desc', $formula);

        // Tanpa syarat ini, pesanan bernilai nol membuat margin dibagi nol.
        $this->assertStringContainsString('G > 0', $formula);
    }

    public function test_margin_reads_the_left_cell_of_each_merged_kpi_card(): void
    {
        // Kartu KPI menggabungkan dua kolom; nilainya hanya ada di sel kiri
        // (A, C, E, G). Rumus yang menunjuk B/D/F/H selalu membaca sel kosong
        // dan menghasilkan 0 — itulah sebabnya margin sempat tampil 0,0%.
        $this->assertStringContainsString('=IFERROR(IF(C7>0,E7/C7,0),0)', $this->script);
        $this->assertStringNotContainsString('F7/D7', $this->script);
        $this->assertStringNotContainsString('=IFERROR(C16/D7,0)', $this->script);
    }

    public function test_no_formula_is_capped_at_a_fixed_row(): void
    {
        // Rentang seperti 'Data Order'!G4:G303 berhenti menghitung begitu data
        // melewatinya, tanpa tanda apa pun di laporan.
        preg_match_all("/'(?:Data Order|Arus Kas|Rincian Biaya HPP)'!\\\$?[A-Z]\\\$?4:\\\$?[A-Z]\\\$?(\d+)/", $this->script, $capped);

        $this->assertSame([], $capped[0], 'Rentang rumus harus terbuka (mis. G4:G), bukan dipatok nomor baris.');
    }

    public function test_script_version_is_bumped_whenever_the_file_changes(): void
    {
        $version = GoogleAppsScriptCode::version();

        $this->assertNotNull($version);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}(\.\d+)?$/', $version);

        // Versinya ikut di setiap respons; tanpa itu panel tidak bisa mendeteksi
        // skrip yang tertinggal di akun Google.
        $this->assertStringContainsString('obj.version = SCRIPT_VERSION;', $this->script);
    }

    public function test_the_chart_has_enough_reserved_rows_to_not_cover_anything(): void
    {
        // Grafik melayang di atas sel, jadi baris di bawahnya harus disediakan.
        // Dulu grafik ditaruh di samping tabel HPP dan menutupi baris keterangan waktu.
        preg_match('/setPosition\((\d+), 1,/', $this->script, $pos);
        preg_match("/setOption\('height', (\d+)\)/", $this->script, $height);
        preg_match('/for \(var gr = (\d+); gr <= (\d+); gr\+\+\) \{ sheet\.setRowHeight\(gr, ROW_H\); \}/', $this->script, $rows);
        preg_match("/getRange\('A(\d+):H\\1'\)\s*\.merge\(\)\s*\.setFormula\('=\"Data terakhir/", $this->script, $stamp);
        preg_match('/var ROW_H = (\d+);/', $this->script, $rowHeight);

        $this->assertNotEmpty($pos, 'Posisi grafik tidak ditemukan.');
        $this->assertNotEmpty($rows, 'Baris cadangan untuk grafik tidak ditemukan.');
        $this->assertNotEmpty($stamp, 'Baris keterangan waktu tidak ditemukan.');
        $this->assertNotEmpty($rowHeight, 'Tinggi baris ROW_H tidak ditemukan.');

        $anchorRow = (int) $pos[1];
        $reserved = ((int) $rows[2] - (int) $rows[1] + 1) * (int) $rowHeight[1];

        $this->assertSame($anchorRow, (int) $rows[1], 'Baris cadangan harus dimulai di baris tempat grafik ditambatkan.');
        $this->assertGreaterThanOrEqual((int) $height[1], $reserved, 'Tinggi grafik melebihi baris yang disediakan.');
        $this->assertGreaterThan((int) $rows[2], (int) $stamp[1], 'Keterangan waktu harus di bawah grafik.');
        $this->assertGreaterThan(16, $anchorRow, 'Grafik harus di bawah tabel HPP, bukan di sampingnya.');
    }

    public function test_chart_gets_a_text_month_axis_and_data_that_is_already_written(): void
    {
        // Sumbu tanggal membuat grafik kolom memakai skala kontinu sepanjang
        // setahun; batangnya menipis sampai tak terlihat. Label bulan harus teks.
        $this->assertStringContainsString('MONTHS_ID[m - 1]', $this->script);
        $this->assertStringNotContainsString("'=DATE(' + yr + ',' + m + ',1)'", $this->script);
        $this->assertStringNotContainsString("setNumberFormat('mmmm yyyy')", $this->script);

        // Batas bulan tidak boleh lagi menumpang kolom A, karena kolom itu teks.
        $this->assertStringNotContainsString('EDATE(A', $this->script);

        $start = strpos($this->script, 'function buildCashChart(');
        $this->assertNotFalse($start, 'buildCashChart tidak ditemukan.');
        $chart = substr($this->script, $start);

        // Apps Script menunda penulisan: tanpa flush, grafik dibangun saat tab
        // Rekap masih kosong dan tidak menemukan satu pun seri.
        $flushAt = strpos($chart, 'SpreadsheetApp.flush();');
        $buildAt = strpos($chart, '.asColumnChart()');

        $this->assertNotFalse($flushAt, 'SpreadsheetApp.flush() tidak dipanggil sebelum grafik dibuat.');
        $this->assertLessThan($buildAt, $flushAt, 'flush() harus dijalankan sebelum grafik dibangun.');

        // Jumlah baris judul dan kolom sumbu dinyatakan, bukan ditebak.
        $this->assertStringContainsString('.setNumHeaders(1)', $chart);
        $this->assertStringContainsString("setOption('useFirstColumnAsDomain', true)", $chart);
    }

    public function test_chart_is_created_after_data_arrives_and_never_replaces_your_own(): void
    {
        // Saat Setup berjalan, tab Arus Kas baru dikosongkan sehingga seluruh
        // angka Rekap masih nol. Grafik yang lahir di keadaan itu tidak
        // menemukan seri apa pun, jadi pembuatannya menunggu data masuk.
        $syncAll = substr($this->script, strpos($this->script, 'function handleSyncAll('));
        $this->assertStringContainsString('buildCashChart(ss);', $syncAll);

        $dashboard = substr(
            $this->script,
            strpos($this->script, 'function setupDashboardSheet('),
            strpos($this->script, 'function setupDataOrderSheet(') - strpos($this->script, 'function setupDashboardSheet(')
        );
        $this->assertStringNotContainsString('buildCashChart(', $dashboard);

        $chart = substr($this->script, strpos($this->script, 'function buildCashChart('));

        // Grafik yang sudah ada dibiarkan, termasuk yang dibuat sendiri lewat
        // Insert > Chart — menata ulang tampilan tidak boleh menghapusnya.
        $this->assertStringContainsString('if (sheet.getCharts().length > 0) { return; }', $chart);

        // Kegagalannya harus terlihat, bukan ditelan blok catch kosong.
        $this->assertStringContainsString('Grafik gagal dibuat: ', $chart);
    }

    public function test_data_rows_share_one_height_across_every_tab(): void
    {
        preg_match('/var ROW_H = (\d+);/', $this->script, $m);
        $this->assertNotEmpty($m, 'ROW_H tidak ditemukan.');
        $this->assertSame(28, (int) $m[1]);

        // Tinggi baris data tidak boleh ditulis sebagai angka lepas di mana pun,
        // supaya tabel di semua tab tetap seragam saat nilainya diubah.
        $this->assertStringContainsString('sheet.setRowHeights(4, dataRowCount(sheet), ROW_H)', $this->script);
        $this->assertStringNotContainsString('sheet.setRowHeights(4, dataRowCount(sheet), 20)', $this->script);
    }

    public function test_cash_ledger_descriptions_do_not_repeat_the_transaction_number(): void
    {
        // No. Transaksi punya kolomnya sendiri; mengulangnya di Keterangan
        // memakan lebar dan menutupi nama barangnya.
        $this->assertStringNotContainsString("+ ' (' + (c.order_number || '') + ')'", $this->script);
        $this->assertStringNotContainsString("c[3] + ' (' + c[1] + ')'", $this->script);
    }

    public function test_analysis_tab_is_never_rebuilt_once_it_has_content(): void
    {
        // Setup mengosongkan tab lain setiap dijalankan. Kalau itu berlaku di
        // tab Analisa, pivot dan filter buatan pengguna hilang tiap kali
        // menekan "Tata Ulang Dashboard".
        preg_match('/function setupAnalisaSheet\(.*?\n\}/s', $this->script, $fn);
        $this->assertNotEmpty($fn, 'Fungsi setupAnalisaSheet tidak ditemukan.');

        $this->assertStringContainsString('getLastRow() > 0', $fn[0]);
        $this->assertStringContainsString('return;', $fn[0]);
        $this->assertStringNotContainsString('sheet.clear()', $fn[0]);

        // ...dan tab itu tidak boleh ikut terhapus oleh pembersihan tab lama.
        preg_match("/var oldTabs = \[(.*?)\];/", $this->script, $old);
        $this->assertStringNotContainsString('Analisa', $old[1]);

        $this->assertStringContainsString('setupAnalisaSheet(ss, orderSheet, costSheet)', $this->script);
    }

    public function test_every_data_tab_has_a_hidden_id_column_for_deletions(): void
    {
        foreach ([15, 10] as $col) {
            $this->assertStringContainsString('setupIdColumn(sheet, '.$col.')', $this->script);
        }

        $this->assertStringContainsString('function handleDelete(', $this->script);
        $this->assertStringContainsString('function refreshSaldoFormulas(', $this->script);
    }
}

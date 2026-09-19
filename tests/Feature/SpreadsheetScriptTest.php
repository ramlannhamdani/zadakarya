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

    public function test_dashboard_categories_match_the_ones_laravel_sends(): void
    {
        preg_match('/var cats = \[(.*?)\];/s', $this->script, $block);
        $this->assertNotEmpty($block, 'Daftar kategori di dashboard tidak ditemukan.');

        preg_match_all("/\['([^']+)'/", $block[1], $found);
        $inScript = $found[1];

        // SUMIF di dashboard mencocokkan label persis. Saat label Laravel berbunyi
        // "Jasa Makloon (Bordir/Sablon)" sementara skrip menulis "Jasa Makloon",
        // kategori itu diam-diam menampilkan Rp0 di laporan.
        $this->assertEqualsCanonicalizing(
            array_values(OrderCost::CATEGORIES),
            $inScript,
            'Label kategori HPP di code.js harus sama persis dengan OrderCost::CATEGORIES.'
        );
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
        preg_match('/for \(var gr = (\d+); gr <= (\d+); gr\+\+\) \{ sheet\.setRowHeight\(gr, (\d+)\); \}/', $this->script, $rows);
        preg_match("/getRange\('A(\d+):H\\1'\)\s*\.merge\(\)\s*\.setFormula\('=\"Data terakhir/", $this->script, $stamp);

        $this->assertNotEmpty($pos, 'Posisi grafik tidak ditemukan.');
        $this->assertNotEmpty($rows, 'Baris cadangan untuk grafik tidak ditemukan.');
        $this->assertNotEmpty($stamp, 'Baris keterangan waktu tidak ditemukan.');

        $anchorRow = (int) $pos[1];
        $reserved = ((int) $rows[2] - (int) $rows[1] + 1) * (int) $rows[3];

        $this->assertSame($anchorRow, (int) $rows[1], 'Baris cadangan harus dimulai di baris tempat grafik ditambatkan.');
        $this->assertGreaterThanOrEqual((int) $height[1], $reserved, 'Tinggi grafik melebihi baris yang disediakan.');
        $this->assertGreaterThan((int) $rows[2], (int) $stamp[1], 'Keterangan waktu harus di bawah grafik.');
        $this->assertGreaterThan(16, $anchorRow, 'Grafik harus di bawah tabel HPP, bukan di sampingnya.');
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

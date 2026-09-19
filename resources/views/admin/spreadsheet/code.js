/**
 * ============================================================================
 * ZADA KARYA PRODUCTION - GOOGLE SPREADSHEET AUTOMATION & WEBHOOK
 * ============================================================================
 * Dashboard Premium v3 — Visual Enhanced, Column-Width Fixed, Mismatch Fixed
 * ============================================================================
 */

/**
 * Versi skrip. Ikut dikirim di setiap respons supaya panel admin bisa
 * memberi tahu kalau skrip yang terpasang di Google sudah tertinggal dari
 * yang ada di aplikasi — penyebab paling sering tampilan sheet terlihat lama.
 * Naikkan setiap kali berkas ini diubah.
 */
var SCRIPT_VERSION = '2026-09-19.9';

/**
 * Tinggi baris data di seluruh tab. Baris judul, banner, dan kartu KPI punya
 * tingginya sendiri karena isinya memang berbeda.
 */
var ROW_H = 28;

/** Nama bulan ditulis sendiri karena locale spreadsheet dipaksa en_US. */
var MONTHS_ID = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

/* ========================================================================== */
/* WEBHOOK HANDLERS                                                            */
/* ========================================================================== */

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.tryLock(20000);

  try {
    if (!e || !e.postData || !e.postData.contents) {
      return respondJSON({ status: 'error', message: 'Payload data kosong.' });
    }

    var data = JSON.parse(e.postData.contents);

    if (data.action === 'setup') {
      setupSheet();
      return respondJSON({ status: 'success', message: 'Setup sheet berhasil!' });
    }

    if (data.action === 'ping') {
      return respondJSON({ status: 'success', message: 'Zada Karya Webhook Service Active' });
    }

    var ss = SpreadsheetApp.getActiveSpreadsheet();

    if (data.action === 'sync_all' || data.orders || Array.isArray(data)) {
      handleSyncAll(data, ss);
      return respondJSON({ status: 'success', message: 'Data synced successfully' });
    }

    if (data.action === 'delete') {
      handleDelete(data, ss);
      return respondJSON({ status: 'success', message: 'Baris dihapus dari sheet' });
    }

    if (data.type === 'order' || data.action === 'upsert_order') {
      handleUpsertOrder(data.order || data.row, ss);
    } else if (data.type === 'cost' || data.action === 'add_cost') {
      handleAddCost(data.cost || data.row, ss);
    } else if (data.type === 'payment' || data.action === 'add_payment') {
      handleAddPayment(data.payment || data.row, ss);
    }

    return respondJSON({ status: 'success', message: 'Data synced successfully' });
  } catch (err) {
    return respondJSON({ status: 'error', message: err.toString() });
  } finally {
    lock.releaseLock();
  }
}

function doGet(e) {
  return respondJSON({ status: 'success', message: 'Zada Karya Webhook Service Active' });
}

function respondJSON(obj) {
  obj.version = SCRIPT_VERSION;

  // Alamat spreadsheet ikut dilaporkan supaya panel admin bisa menautkannya
  // langsung. URL webhook tidak memuat id spreadsheet, jadi hanya skrip ini —
  // yang memang terpasang di dalamnya — yang tahu alamatnya.
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    if (ss) {
      obj.sheet_url = ss.getUrl();
      obj.sheet_name = ss.getName();
    }
  } catch (e) {}

  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

/* ========================================================================== */
/* MAIN SETUP                                                                  */
/* ========================================================================== */

function setupSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  try {
    ss.setSpreadsheetLocale('en_US');
    ss.setSpreadsheetTimeZone('Asia/Jakarta');
  } catch(e) {}

  var cashSheet   = getOrCreateSheet(ss, 'Arus Kas');
  var orderSheet  = getOrCreateSheet(ss, 'Data Order');
  var costSheet   = getOrCreateSheet(ss, 'Rincian Biaya HPP');
  var rekapSheet  = getOrCreateSheet(ss, 'Rekap Bulanan');
  var dashSheet   = getOrCreateSheet(ss, 'Dashboard');

  setupArusKasSheet(cashSheet);
  setupDataOrderSheet(orderSheet);
  setupRincianHppSheet(costSheet);
  setupRekapBulananSheet(rekapSheet);
  setupDashboardSheet(dashSheet, rekapSheet);
  setupAnalisaSheet(ss, orderSheet, costSheet);

  var oldTabs = ['Sheet1', 'Data_Pesanan', 'Data_Biaya_HPP', 'Data_Pembayaran', 'Dashboard Laporan'];
  for (var i = 0; i < oldTabs.length; i++) {
    var oldSheet = ss.getSheetByName(oldTabs[i]);
    if (oldSheet && ss.getSheets().length > 1) {
      try { ss.deleteSheet(oldSheet); } catch(e) {}
    }
  }

  ss.setActiveSheet(dashSheet);
  ss.moveActiveSheet(1);
}

/* ========================================================================== */
/* COLOR PALETTE                                                               */
/* ========================================================================== */
var CLR = {
  navy:       '#1F3864',
  blue:       '#2E5FAC',
  blueLight:  '#D6E4F7',
  blueCard:   '#EBF3FB',
  green:      '#1A6B36',
  greenLight: '#D6F0E0',
  greenCard:  '#E8F6ED',
  red:        '#B22222',
  redLight:   '#FAD7D7',
  redCard:    '#FDECEC',
  amber:      '#8B5E00',
  amberLight: '#FFF0CC',
  amberCard:  '#FFF8E6',
  purple:     '#5B2D8E',
  purpleCard: '#F0EAF8',
  slate:      '#2A3F54',
  slateCard:  '#F0F2F5',
  white:      '#FFFFFF',
  headerText: '#FFFFFF',
  rowEven:    '#F7F9FC',
  rowOdd:     '#FFFFFF',
  border:     '#CBD5E1',
  subHeader:  '#EEF2F7',
  gray1:      '#F8FAFC',
  gray2:      '#64748B',
  total:      '#1F3864'
};

/* ========================================================================== */
/* GRAFIK ARUS KAS                                                             */
/* ========================================================================== */

/**
 * Buat grafik batang di Dashboard dari tab Rekap Bulanan — hanya bila belum ada.
 *
 * Dipanggil di akhir sinkronisasi, bukan saat menata ulang tampilan. Waktu
 * Setup berjalan, tab Arus Kas baru saja dikosongkan sehingga seluruh angka
 * Rekap masih nol, dan grafik yang lahir di keadaan itu tidak menemukan seri
 * apa pun — yang tersisa hanya judulnya.
 *
 * `setNumHeaders(1)` adalah kuncinya: tanpa itu baris judul kolom ikut terbaca
 * sebagai data, sehingga kolom angkanya disimpulkan sebagai kolom teks dan
 * tidak ada satu pun seri yang terbentuk. Grafik bawaan Google mengenali baris
 * judul itu sendiri, makanya Insert > Chart berhasil sementara ini tidak.
 */
function buildCashChart(ss) {
  var sheet = ss.getSheetByName('Dashboard');
  var rekapSheet = ss.getSheetByName('Rekap Bulanan');
  if (!sheet || !rekapSheet) { return; }

  // Grafik yang sudah ada dibiarkan — termasuk kalau Anda menggantinya sendiri
  // lewat Insert > Chart. Menata ulang tampilan tidak akan menghapusnya lagi.
  if (sheet.getCharts().length > 0) { return; }

  // Rumus Rekap harus sudah benar-benar tertulis sebelum grafik membacanya.
  SpreadsheetApp.flush();

  try {
    var chart = sheet.newChart()
      .asColumnChart()
      .addRange(rekapSheet.getRange('A3:C15'))
      .setNumHeaders(1)
      .setPosition(29, 1, 4, 0)
      .setOption('useFirstColumnAsDomain', true)
      .setOption('title', 'Kas Masuk vs Kas Keluar per Bulan')
      .setOption('titleTextStyle', { fontSize: 12, bold: true, color: CLR.navy })
      .setOption('legend', { position: 'top' })
      .setOption('colors', ['#1A6B36', '#B22222'])
      .setOption('hAxis', { slantedText: true, slantedTextAngle: 45 })
      .setOption('width', 1340)
      .setOption('height', 330)
      .build();

    sheet.insertChart(chart);
    sheet.getRange('A44').clearContent();
  } catch (e) {
    // Dulu kegagalan di sini ditelan diam-diam, jadi tidak ada cara tahu
    // apakah grafiknya gagal dibuat atau hanya tidak menemukan data.
    sheet.getRange('A44').setValue('Grafik gagal dibuat: ' + e)
      .setFontSize(9).setFontColor(CLR.red);
  }
}

/* ========================================================================== */
/* TAB: ANALISA (PIVOT)                                                        */
/* ========================================================================== */

/**
 * Tab untuk menggali sendiri: pivot bisa diubah, ditambah filter, atau diganti
 * pengelompokannya tanpa menyentuh kode.
 *
 * Berbeda dari tab lain, tab ini TIDAK dibangun ulang kalau sudah ada isinya.
 * Setup mengosongkan tab lain setiap kali dijalankan; kalau itu berlaku di sini,
 * pivot dan filter yang Anda susun sendiri akan hilang tiap kali menekan
 * "Tata Ulang Dashboard".
 */
function setupAnalisaSheet(ss, orderSheet, costSheet) {
  var sheet = ss.getSheetByName('Analisa');

  if (sheet && sheet.getLastRow() > 0) {
    return; // sudah dipakai — biarkan apa adanya
  }

  if (!sheet) { sheet = ss.insertSheet('Analisa'); }

  sheet.setColumnWidth(1, 200);
  for (var c = 2; c <= 9; c++) { sheet.setColumnWidth(c, 150); }

  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:I1').merge()
    .setValue('ANALISA  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:I2').setBackground('#F59E0B');

  sheet.setRowHeight(3, 34);
  sheet.getRange('A3:I3').merge()
    .setValue('Tabel di bawah bisa Anda ubah sendiri — klik pivotnya, lalu atur baris, kolom, '
            + 'dan filter lewat panel di kanan. Isi tab ini tidak akan ditimpa saat menekan "Tata Ulang Dashboard".')
    .setFontSize(9).setFontColor(CLR.gray2).setBackground(CLR.gray1)
    .setVerticalAlignment('middle').setWrap(true);

  sheet.getRange('A5').setValue('LABA PER CUSTOMER')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.navy);
  sheet.getRange('F5').setValue('BIAYA PER KATEGORI')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.navy);

  // Rentang sumber menyertakan baris judul (baris 3) supaya pivot tahu nama kolomnya.
  try {
    var orderSource = orderSheet.getRange(3, 1, orderSheet.getMaxRows() - 2, 14);
    var p1 = sheet.getRange('A6').createPivotTable(orderSource);
    var group1 = p1.addRowGroup(3); // Customer

    var totalOrder = p1.addPivotValue(7, SpreadsheetApp.PivotTableSummarizeFunction.SUM);
    var totalHpp = p1.addPivotValue(10, SpreadsheetApp.PivotTableSummarizeFunction.SUM);
    var profit = p1.addPivotValue(11, SpreadsheetApp.PivotTableSummarizeFunction.SUM);

    totalOrder.setDisplayName('Nilai Order');
    totalHpp.setDisplayName('Total HPP');
    profit.setDisplayName('Estimasi Laba');

    // Customer paling menguntungkan di atas. Kalau versi Sheets menolak
    // pengurutan ini, pivotnya tetap terbentuk, hanya urut nama.
    try { group1.sortDescending().sortBy(profit, []); } catch (e) {}
  } catch (e) {}

  try {
    var costSource = costSheet.getRange(3, 1, costSheet.getMaxRows() - 2, 9);
    var p2 = sheet.getRange('F6').createPivotTable(costSource);
    p2.addRowGroup(3); // Kategori HPP

    var biaya = p2.addPivotValue(8, SpreadsheetApp.PivotTableSummarizeFunction.SUM);
    biaya.setDisplayName('Total Biaya');
  } catch (e) {}
}

/* ========================================================================== */
/* TAB: DASHBOARD                                                              */
/* ========================================================================== */
function setupDashboardSheet(sheet, rekapSheet) {
  sheet.clear();
  var charts = sheet.getCharts();
  for (var i = 0; i < charts.length; i++) { sheet.removeChart(charts[i]); }

  // Delapan kolom selebar sama supaya kartu KPI yang menggabung dua kolom
  // selalu sama besar — lebar campur membuat kartunya terlihat miring.
  for (var c = 1; c <= 8; c++) { sheet.setColumnWidth(c, 175); }

  // ── ROW 1–2: BANNER ──────────────────────────────────────────────────────
  sheet.setRowHeight(1, 52);
  sheet.getRange('A1:H1').merge()
    .setValue('DASHBOARD KEUANGAN & OPERASIONAL  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(14).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  sheet.setRowHeight(2, 5);
  sheet.getRange('A2:H2').setBackground('#F59E0B');

  // ── ROW 3–4 & 6–7: KARTU KPI ─────────────────────────────────────────────
  // Nilai kartu berada di sel KIRI dari pasangan yang digabung (A4, C4, E4, G4).
  // Rumus yang menunjuk sel kanan (B, D, F, H) selalu membaca sel kosong.
  var kpiRows = [
    {
      labelRow: 3, valueRow: 4,
      cards: [
        { col: 'A', label: 'Saldo Kas Saat Ini',
          formula: "=IFERROR(SUM('Arus Kas'!F4:F)-SUM('Arus Kas'!G4:G),0)",
          bg: CLR.blueCard, fg: CLR.blue, fmt: '"Rp"#,##0;[Red]-"Rp"#,##0' },
        { col: 'C', label: 'Kas Masuk Bulan Ini',
          formula: "=IFERROR(SUMIFS('Arus Kas'!F4:F,'Arus Kas'!A4:A,\">=\"&EOMONTH(TODAY(),-1)+1,'Arus Kas'!A4:A,\"<=\"&EOMONTH(TODAY(),0)),0)",
          bg: CLR.greenCard, fg: CLR.green, fmt: '"Rp"#,##0' },
        { col: 'E', label: 'Kas Keluar Bulan Ini',
          formula: "=IFERROR(SUMIFS('Arus Kas'!G4:G,'Arus Kas'!A4:A,\">=\"&EOMONTH(TODAY(),-1)+1,'Arus Kas'!A4:A,\"<=\"&EOMONTH(TODAY(),0)),0)",
          bg: CLR.redCard, fg: CLR.red, fmt: '"Rp"#,##0' },
        { col: 'G', label: 'Arus Kas Bersih Bulan Ini',
          formula: '=C4-E4',
          bg: CLR.slateCard, fg: CLR.slate, fmt: '"Rp"#,##0;[Red]-"Rp"#,##0' }
      ]
    },
    {
      labelRow: 6, valueRow: 7,
      cards: [
        { col: 'A', label: 'Total Sisa Piutang',
          formula: "=IFERROR(SUM('Data Order'!L4:L),0)",
          bg: CLR.amberCard, fg: CLR.amber, fmt: '"Rp"#,##0' },
        { col: 'C', label: 'Total Nilai Order',
          formula: "=IFERROR(SUM('Data Order'!G4:G),0)",
          bg: CLR.blueCard, fg: CLR.blue, fmt: '"Rp"#,##0' },
        { col: 'E', label: 'Estimasi Laba Kotor',
          formula: "=IFERROR(SUM('Data Order'!K4:K),0)",
          bg: CLR.greenCard, fg: CLR.green, fmt: '"Rp"#,##0;[Red]-"Rp"#,##0' },
        { col: 'G', label: 'Margin Keuntungan',
          formula: '=IFERROR(IF(C7>0,E7/C7,0),0)',
          bg: CLR.purpleCard, fg: CLR.purple, fmt: '0.0%' }
      ]
    }
  ];

  for (var g = 0; g < kpiRows.length; g++) {
    var group = kpiRows[g];
    sheet.setRowHeight(group.labelRow, 22);
    sheet.setRowHeight(group.valueRow, 48);

    for (var n = 0; n < group.cards.length; n++) {
      var k = group.cards[n];
      var right = String.fromCharCode(k.col.charCodeAt(0) + 1);

      sheet.getRange(k.col + group.labelRow + ':' + right + group.labelRow).merge()
        .setValue(k.label)
        .setFontSize(9).setFontWeight('bold').setFontColor(CLR.gray2).setBackground(k.bg)
        .setHorizontalAlignment('center').setVerticalAlignment('middle');

      sheet.getRange(k.col + group.valueRow + ':' + right + group.valueRow).merge()
        .setFormula(k.formula)
        .setFontSize(17).setFontWeight('bold').setFontColor(k.fg).setBackground(k.bg)
        .setHorizontalAlignment('center').setVerticalAlignment('middle')
        .setNumberFormat(k.fmt)
        .setBorder(null, null, true, null, null, null, k.fg, SpreadsheetApp.BorderStyle.SOLID_THICK);
    }
  }

  sheet.setRowHeight(5, 12);
  sheet.setRowHeight(8, 18);

  /* ── ROW 9–16: KOMPOSISI HPP (kiri) & RINGKASAN PESANAN (kanan) ────────── */

  sheet.setRowHeight(9, 26);
  sheet.getRange('A9:D9').merge()
    .setValue('KOMPOSISI BIAYA PRODUKSI (HPP)  —  terbesar di atas')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(false);
  sheet.getRange('E9:H9').merge()
    .setValue('RINGKASAN PESANAN')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.slate)
    .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(false);

  sheet.setRowHeight(10, 24);
  sheet.getRange(10, 1, 1, 4).setValues([['Kategori HPP', 'Total Biaya', '% Porsi', 'Perbandingan']])
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.navy).setBackground(CLR.blueLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // Kategori dibaca langsung dari datanya dan diurutkan dari biaya terbesar,
  // bukan dari daftar tetap di dalam kode. Selain menjawab "biaya paling besar
  // apa", ini juga menghapus seluruh kemungkinan label meleset — dulu "Jasa
  // Makloon" tidak cocok dengan label Laravel dan diam-diam tampil Rp0.
  // A11 meluber ke A11:B15; JANGAN isi sel di rentang itu atau rumusnya gagal.
  sheet.getRange('A11').setFormula(
    '=IFERROR(QUERY(\'Rincian Biaya HPP\'!A4:I,'
    + '"select C, sum(H) where C is not null group by C order by sum(H) desc limit 5 label sum(H) \'\'",0),"")'
  );

  for (var r = 11; r <= 15; r++) {
    sheet.setRowHeight(r, ROW_H);
    var rowBg = (r % 2 === 1) ? CLR.rowOdd : CLR.rowEven;
    sheet.getRange(r, 1, 1, 4).setBackground(rowBg).setVerticalAlignment('middle');

    sheet.getRange(r, 1).setFontSize(9).setFontWeight('bold').setWrap(false);
    sheet.getRange(r, 2).setFontSize(9).setFontWeight('bold').setNumberFormat('"Rp"#,##0');
    sheet.getRange(r, 3)
      .setFormula('=IFERROR(IF($B$16>0,B' + r + '/$B$16,""),"")')
      .setFontSize(9).setNumberFormat('0.0%').setHorizontalAlignment('center');
    sheet.getRange(r, 4)
      .setFormula('=IFERROR(SPARKLINE(B' + r + ',{"charttype","bar";"max",MAX($B$11:$B$15);"color1","' + CLR.blue + '"}),"")');
  }

  sheet.setRowHeight(16, 26);
  sheet.getRange('A16').setValue('TOTAL BIAYA HPP')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setHorizontalAlignment('right').setVerticalAlignment('middle');
  sheet.getRange('B16').setFormula('=IFERROR(SUM(B11:B15),0)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setNumberFormat('"Rp"#,##0');
  sheet.getRange('C16').setFormula('=IFERROR(SUM(C11:C15),0)')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setNumberFormat('0.0%').setHorizontalAlignment('center');
  sheet.getRange('D16').setBackground(CLR.blue);

  var summary = [
    ['Jumlah Pesanan',        "=IFERROR(COUNTA('Data Order'!B4:B),0)",                              '#,##0'],
    ['Masih Berjalan',        "=IFERROR(COUNTIF('Data Order'!M4:M,\"Aktif*\"),0)",                  '#,##0'],
    ['Sudah Selesai',         "=IFERROR(COUNTIF('Data Order'!M4:M,\"Selesai*\"),0)",                '#,##0'],
    ['Sudah Lunas',           "=IFERROR(COUNTIFS('Data Order'!B4:B,\"<>\",'Data Order'!L4:L,0),0)", '#,##0'],
    ['Rata-rata Nilai Order', "=IFERROR(AVERAGE('Data Order'!G4:G),0)",                             '"Rp"#,##0']
  ];

  for (var s = 0; s < summary.length; s++) {
    var sr = 11 + s;
    var sBg = (sr % 2 === 1) ? CLR.rowOdd : CLR.rowEven;

    sheet.getRange(sr, 5, 1, 4).setBackground(sBg).setVerticalAlignment('middle');
    sheet.getRange('E' + sr + ':G' + sr).merge().setValue(summary[s][0])
      .setFontSize(9).setFontColor(CLR.slate).setVerticalAlignment('middle');
    sheet.getRange('H' + sr).setFormula(summary[s][1])
      .setFontSize(10).setFontWeight('bold').setFontColor(CLR.slate)
      .setNumberFormat(summary[s][2]).setHorizontalAlignment('right').setVerticalAlignment('middle');
  }
  sheet.getRange('E16:H16').merge().setBackground(CLR.slate);

  /* ── ROW 18–27: MARGIN PER PESANAN ─────────────────────────────────────── */

  sheet.setRowHeight(17, 14);
  sheet.setRowHeight(18, 26);
  sheet.getRange('A18:H18').merge()
    .setValue('MARGIN PER PESANAN  —  margin terbesar di atas')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.green)
    .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(false);

  sheet.setRowHeight(19, 24);
  sheet.getRange(19, 1, 1, 6)
    .setValues([['No. Order', 'Customer', 'Nilai Order', 'Total HPP', 'Estimasi Laba', 'Margin']])
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.green).setBackground(CLR.greenLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.getRange('G19:H19').merge().setValue('Perbandingan Margin')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.green).setBackground(CLR.greenLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // Pesanan tanpa nilai order dilewati supaya margin tidak dibagi nol.
  // A20 meluber ke A20:F27; jangan isi sel di rentang itu.
  sheet.getRange('A20').setFormula(
    '=IFERROR(QUERY(\'Data Order\'!A4:N,'
    + '"select B, C, G, J, K, K/G where B is not null and G > 0 order by K/G desc limit 8 label K/G \'\'",0),"")'
  );

  for (var mr = 20; mr <= 27; mr++) {
    sheet.setRowHeight(mr, ROW_H);
    var mBg = (mr % 2 === 0) ? CLR.rowOdd : CLR.rowEven;
    sheet.getRange(mr, 1, 1, 8).setBackground(mBg).setVerticalAlignment('middle');

    sheet.getRange(mr, 1).setFontSize(9).setFontWeight('bold').setFontColor(CLR.navy);
    sheet.getRange(mr, 2).setFontSize(9);
    sheet.getRange(mr, 3, 1, 2).setFontSize(9).setNumberFormat('"Rp"#,##0');
    sheet.getRange(mr, 5).setFontSize(9).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
    sheet.getRange(mr, 6).setFontSize(9).setFontWeight('bold')
      .setNumberFormat('0.0%;[Red]-0.0%').setHorizontalAlignment('center');

    sheet.getRange('G' + mr + ':H' + mr).merge()
      .setFormula('=IFERROR(IF(F' + mr + '="","",SPARKLINE(MAX(F' + mr + ',0),'
        + '{"charttype","bar";"max",MAX($F$20:$F$27);"color1","' + CLR.green + '"})),"")');
  }

  /* ── ROW 29–45: GRAFIK ─────────────────────────────────────────────────── */
  // Grafik melayang di atas sel, jadi barisnya disediakan setinggi grafiknya.
  sheet.setRowHeight(28, 14);
  for (var gr = 29; gr <= 40; gr++) { sheet.setRowHeight(gr, ROW_H); }

  // Grafik sengaja TIDAK dibuat di sini. Saat menata ulang, tab Arus Kas baru
  // dikosongkan sehingga seluruh angka Rekap masih nol — grafik yang lahir di
  // keadaan itu tidak menemukan seri apa pun. Ia dibuat di akhir sinkronisasi,
  // ketika datanya sudah ada.
  var oldCharts = sheet.getCharts();
  for (var oc = 0; oc < oldCharts.length; oc++) { sheet.removeChart(oldCharts[oc]); }

  /* ── ROW 47: KETERANGAN WAKTU ──────────────────────────────────────────── */
  sheet.setRowHeight(41, 10);
  sheet.setRowHeight(42, 22);
  sheet.getRange('A42:H42').merge()
    .setFormula('="Data terakhir diperbarui: "&TEXT(NOW(),"dd mmmm yyyy, HH:mm")&" WIB"')
    .setFontSize(9).setFontColor(CLR.gray2).setBackground(CLR.gray1)
    .setHorizontalAlignment('right').setVerticalAlignment('middle');

  sheet.setFrozenRows(2);
}

/* ========================================================================== */
/* TAB: DATA ORDER                                                             */
/* ========================================================================== */
function setupDataOrderSheet(sheet) {
  sheet.clear();

  // Column widths — fine-tuned for readability
  sheet.setColumnWidth(1, 90);   // A: Tanggal
  sheet.setColumnWidth(2, 135);  // B: No. Order
  sheet.setColumnWidth(3, 160);  // C: Customer — nama instansi kerap panjang
  sheet.setColumnWidth(4, 200);  // D: Produk
  sheet.setColumnWidth(5, 55);   // E: Qty
  sheet.setColumnWidth(6, 90);   // F: Harga/pcs
  sheet.setColumnWidth(7, 115);  // G: Nilai Order
  sheet.setColumnWidth(8, 105);  // H: DP
  sheet.setColumnWidth(9, 105);  // I: Pelunasan
  sheet.setColumnWidth(10, 115); // J: Total HPP
  sheet.setColumnWidth(11, 115); // K: Estimasi Laba
  sheet.setColumnWidth(12, 115); // L: Sisa Tagihan
  sheet.setColumnWidth(13, 170); // M: Status
  sheet.setColumnWidth(14, 200); // N: Catatan

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:N1').merge()
    .setValue('🛒  DATA ORDER & PROFITABILITAS  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:N2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var headers = [
    'Tanggal', 'No. Order', 'Customer', 'Nama Produk / Item', 'Qty',
    'Harga / pcs', 'Nilai Order', 'DP Diterima', 'Pelunasan',
    'Total HPP', 'Estimasi Laba', 'Sisa Tagihan', 'Status', 'Catatan'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 15);

  // Format dipasang untuk seluruh kolom, bukan sampai baris tertentu.
  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 5, rows, 1).setNumberFormat('#,##0').setHorizontalAlignment('center');
  sheet.getRange(4, 6, rows, 7).setNumberFormat('"Rp"#,##0');
  // Estimasi laba bisa minus kalau HPP melampaui nilai order.
  sheet.getRange(4, 11, rows, 1).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  sheet.getRange(4, 13, rows, 1).setHorizontalAlignment('center').setWrap(false);
  sheet.getRange(4, 14, rows, 1).setWrap(true);

  applyRowStripes(sheet, 15);
}

/* ========================================================================== */
/* TAB: ARUS KAS                                                               */
/* ========================================================================== */
function setupArusKasSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 85);   // A: Tanggal
  sheet.setColumnWidth(2, 145);  // B: No. Transaksi
  sheet.setColumnWidth(3, 310);  // C: Keterangan — memuat nama bahan + no. order
  sheet.setColumnWidth(4, 185);  // D: Kategori — "Jasa Makloon (Bordir/Sablon)" harus utuh
  sheet.setColumnWidth(5, 165);  // E: Customer/Supplier
  sheet.setColumnWidth(6, 120);  // F: Kas Masuk
  sheet.setColumnWidth(7, 120);  // G: Kas Keluar
  sheet.setColumnWidth(8, 120);  // H: Saldo
  sheet.setColumnWidth(9, 130);  // I: Metode

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:I1').merge()
    .setValue('💵  ARUS KAS HARIAN  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:I2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Tanggal', 'No. Transaksi', 'Keterangan', 'Kategori', 'Pihak (Customer / Supplier)',
    'Kas Masuk', 'Kas Keluar', 'Saldo Berjalan', 'Metode'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 10);

  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 6, rows, 2).setNumberFormat('"Rp"#,##0');
  // Saldo berjalan bisa minus saat belanja mendahului pembayaran — angkanya
  // harus terbaca sebagai minus, bukan sekadar angka hitam biasa.
  sheet.getRange(4, 8, rows, 1).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');

  // Rumus Saldo TIDAK dipra-isi di sini. Sel berisi rumus terhitung "ada isinya"
  // oleh getLastRow(), sehingga baris baru dari panel admin dulu mendarat di
  // baris 501 — jauh di bawah datanya. Rumus ditulis bersama barisnya.

  applyRowStripes(sheet, 10);

  // Kas Masuk = green, Kas Keluar = red font color (Column F & G label alignment)
  sheet.getRange('F3').setBackground('#1A6B36');
  sheet.getRange('G3').setBackground('#B22222');
  sheet.getRange('H3').setBackground('#1F3864');
}

/* ========================================================================== */
/* TAB: RINCIAN BIAYA HPP                                                     */
/* ========================================================================== */
function setupRincianHppSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 85);   // A: Tanggal
  sheet.setColumnWidth(2, 150);  // B: No. Order
  sheet.setColumnWidth(3, 190);  // C: Kategori — label penuh dari Laravel
  sheet.setColumnWidth(4, 240);  // D: Rincian Biaya
  sheet.setColumnWidth(5, 60);   // E: Qty
  sheet.setColumnWidth(6, 70);   // F: Satuan
  sheet.setColumnWidth(7, 120);  // G: Harga Satuan
  sheet.setColumnWidth(8, 120);  // H: Total Biaya
  sheet.setColumnWidth(9, 130);  // I: Dicatat Oleh

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:I1').merge()
    .setValue('⚙  RINCIAN BIAYA PRODUKSI (HPP)  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:I2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Tanggal', 'No. Order', 'Kategori HPP', 'Rincian / Deskripsi Biaya',
    'Qty', 'Satuan', 'Harga Satuan', 'Total Biaya', 'Dicatat Oleh'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 10);

  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 5, rows, 1).setNumberFormat('#,##0.##').setHorizontalAlignment('center');
  sheet.getRange(4, 6, rows, 1).setHorizontalAlignment('center');
  sheet.getRange(4, 7, rows, 2).setNumberFormat('"Rp"#,##0');

  applyRowStripes(sheet, 10);
}

/* ========================================================================== */
/* TAB: REKAP BULANAN                                                         */
/* ========================================================================== */
function setupRekapBulananSheet(sheet) {
  sheet.clear();

  // Column widths — wider for currency readability
  sheet.setColumnWidth(1, 130); // A: Bulan
  sheet.setColumnWidth(2, 145); // B: Kas Masuk
  sheet.setColumnWidth(3, 145); // C: Kas Keluar
  sheet.setColumnWidth(4, 145); // D: Arus Kas Bersih
  sheet.setColumnWidth(5, 145); // E: Nilai Order
  sheet.setColumnWidth(6, 145); // F: Laba Order
  sheet.setColumnWidth(7, 145); // G: Sisa Piutang

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:G1').merge()
    .setValue('📅  REKAPITULASI KEUANGAN BULANAN  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:G2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Bulan', 'Kas Masuk', 'Kas Keluar', 'Arus Kas Bersih',
    'Total Nilai Order', 'Estimasi Laba', 'Sisa Piutang'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  var yr = new Date().getFullYear();
  var rows = [];

  // Kolom Bulan diisi TEKS, bukan tanggal. Grafik kolom yang sumbunya berisi
  // tanggal diperlakukan sebagai sumbu kontinu sepanjang setahun, sehingga
  // batangnya menipis sampai tak terlihat — itu sebabnya grafik tampak kosong.
  // Batas bulannya sekarang ditulis eksplisit, tidak lagi menumpang kolom A.
  for (var m = 1; m <= 12; m++) {
    var r = 3 + m;
    var from = 'DATE(' + yr + ',' + m + ',1)';
    var to = 'DATE(' + yr + ',' + (m + 1) + ',1)'; // DATE(th,13,1) = Januari tahun berikutnya

    rows.push([
      MONTHS_ID[m - 1] + ' ' + yr,
      "=SUMIFS('Arus Kas'!$F$4:$F,'Arus Kas'!$A$4:$A,\">=\"&" + from + ",'Arus Kas'!$A$4:$A,\"<\"&" + to + ")",
      "=SUMIFS('Arus Kas'!$G$4:$G,'Arus Kas'!$A$4:$A,\">=\"&" + from + ",'Arus Kas'!$A$4:$A,\"<\"&" + to + ")",
      '=B' + r + '-C' + r,
      "=SUMIFS('Data Order'!$G$4:$G,'Data Order'!$A$4:$A,\">=\"&" + from + ",'Data Order'!$A$4:$A,\"<\"&" + to + ")",
      "=SUMIFS('Data Order'!$K$4:$K,'Data Order'!$A$4:$A,\">=\"&" + from + ",'Data Order'!$A$4:$A,\"<\"&" + to + ")",
      "=SUMIFS('Data Order'!$L$4:$L,'Data Order'!$A$4:$A,\">=\"&" + from + ",'Data Order'!$A$4:$A,\"<\"&" + to + ")"
    ]);
  }

  // setValues, bukan setFormulas: kolom pertama kini teks biasa.
  sheet.getRange(4, 1, rows.length, hdrs.length).setValues(rows);
  sheet.getRange('A4:A15').setHorizontalAlignment('left');
  sheet.getRange('B4:G15').setNumberFormat('"Rp"#,##0');

  // Alternating row colors
  for (var ri = 4; ri <= 15; ri++) {
    var bg = (ri % 2 === 0) ? '#EBF3FB' : CLR.rowOdd;
    sheet.setRowHeight(ri, ROW_H);
    sheet.getRange(ri, 1, 1, 7).setBackground(bg);
    sheet.getRange(ri, 1).setFontWeight('bold');
    sheet.getRange(ri, 4).setFontWeight('bold');
  }

  // Arus kas bersih dulu selalu dicetak hijau, termasuk saat minus. Warnanya
  // sekarang mengikuti nilainya lewat format angka: merah dan diberi tanda.
  sheet.getRange('D4:D16').setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');

  // ROW 16: TOTAL
  sheet.setRowHeight(16, 28);
  sheet.getRange(16, 1).setValue('TOTAL TAHUN ' + yr)
    .setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center');
  for (var col = 2; col <= 7; col++) {
    var cl = String.fromCharCode(64 + col);
    sheet.getRange(16, col)
      .setFormula('=SUM(' + cl + '4:' + cl + '15)')
      .setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.navy)
      .setNumberFormat('"Rp"#,##0').setHorizontalAlignment('right');
  }

  // Separator line above total
  sheet.getRange('A15:G15').setBorder(null, null, true, null, null, null,
    CLR.navy, SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
}

/* ========================================================================== */
/* DATA HANDLERS                                                               */
/* ========================================================================== */
function handleSyncAll(data, ss) {
  // 1. Data Order
  var orderSheet = ss.getSheetByName('Data Order');
  if (orderSheet && data.orders) {
    clearSheetDataRows(orderSheet, 4);
    if (data.orders.length > 0) {
      var orderRows = data.orders.map(function(o) {
        return [
          o.date, o.order_number, o.customer,
          (o.product || '-'),   // Pastikan product string, bukan angka
          o.qty, o.price_per_unit, o.total_order, o.dp, o.settlement,
          o.total_cost, o.estimated_profit, o.remaining, o.status, o.notes,
          o.id || ''
        ];
      });
      orderSheet.getRange(4, 1, orderRows.length, orderRows[0].length).setValues(orderRows);
    }
  }

  // 2. Rincian Biaya HPP
  var costSheet = ss.getSheetByName('Rincian Biaya HPP');
  if (costSheet && data.costs) {
    clearSheetDataRows(costSheet, 4);
    if (data.costs.length > 0) {
      var costRows = data.costs.map(function(c) {
        return [
          c.date, c.order_number, c.category, c.description,
          c.qty, c.unit, c.unit_price, c.amount, c.recorded_by,
          c.id || ''
        ];
      });
      costSheet.getRange(4, 1, costRows.length, costRows[0].length).setValues(costRows);
    }
  }

  // 3. Arus Kas (Kas Masuk dari pembayaran + Kas Keluar dari HPP)
  var cashSheet = ss.getSheetByName('Arus Kas');
  if (cashSheet) {
    clearSheetDataRows(cashSheet, 4);
    var entries = [];

    if (data.payments && data.payments.length > 0) {
      data.payments.forEach(function(p) {
        var isDP = (p.note && p.note.toLowerCase().indexOf('dp') !== -1) ||
                   (p.category && p.category.toLowerCase().indexOf('dp') !== -1);
        entries.push({
          id:      p.id || '',
          date:    p.date,
          trx_no:  p.trx_no || p.order_number,
          desc:    p.desc || ('Pembayaran ' + (p.category || '') + ' — ' + (p.customer || '')),
          cat:     isDP ? 'Penjualan / DP' : 'Pelunasan',
          party:   p.customer || '-',
          inflow:  p.amount || 0,
          outflow: 0,
          method:  p.method || 'Transfer Bank'
        });
      });
    }

    if (data.costs && data.costs.length > 0) {
      data.costs.forEach(function(c) {
        entries.push({
          id:      c.id || '',
          date:    c.date,
          trx_no:  c.order_number,
          desc:    c.description || 'Biaya HPP',
          cat:     c.category || 'Biaya Produksi',
          party:   'Vendor / Tim Produksi',
          inflow:  0,
          outflow: c.amount || 0,
          method:  'Transfer / Kas'
        });
      });
    }

    entries.sort(function(a, b) { return (a.date || '').localeCompare(b.date || ''); });

    if (entries.length > 0) {
      var cashRows = entries.map(function(e, idx) {
        var rowNum = 4 + idx;
        return [
          e.date, e.trx_no, e.desc, e.cat, e.party,
          e.inflow, e.outflow,
          '=IF(A' + rowNum + '="","",SUM($F$4:F' + rowNum + ')-SUM($G$4:G' + rowNum + '))',
          e.method, e.id
        ];
      });
      cashSheet.getRange(4, 1, cashRows.length, cashRows[0].length).setValues(cashRows);
    }
  }

  // Grafik dibangun ulang di sini, bukan hanya saat Setup: waktu Setup berjalan
  // tab Arus Kas baru dikosongkan, sehingga seluruh angka Rekap masih nol.
  buildCashChart(ss);
}

function handleUpsertOrder(orderData, ss) {
  var sheet = ss.getSheetByName('Data Order');
  if (!sheet) return;

  var o = Array.isArray(orderData) ? orderData : [
    orderData.date, orderData.order_number, orderData.customer,
    (orderData.product || '-'),
    orderData.qty, orderData.price_per_unit, orderData.total_order,
    orderData.dp, orderData.settlement, orderData.total_cost,
    orderData.estimated_profit, orderData.remaining, orderData.status, orderData.notes,
    orderData.id || ''
  ];

  var lastRow = sheet.getLastRow();
  var foundRow = -1;

  if (lastRow >= 4) {
    var vals = sheet.getRange(4, 2, lastRow - 3, 1).getValues();
    for (var i = 0; i < vals.length; i++) {
      if (vals[i][0] === o[1]) { foundRow = i + 4; break; }
    }
  }

  if (foundRow > 0) {
    sheet.getRange(foundRow, 1, 1, o.length).setValues([o]);
  } else {
    sheet.appendRow(o);
  }
}

function handleAddCost(costData, ss) {
  var costSheet = ss.getSheetByName('Rincian Biaya HPP');
  var cashSheet = ss.getSheetByName('Arus Kas');

  var c = Array.isArray(costData) ? costData : [
    costData.date, costData.order_number, costData.category, costData.description,
    costData.qty, costData.unit, costData.unit_price, costData.amount, costData.recorded_by,
    costData.id || ''
  ];

  // Biaya yang sama disimpan ulang menimpa barisnya, tidak menumpuk baris baru.
  if (costSheet) {
    var costRow = findRowById(costSheet, 10, c[9]);
    if (costRow > 0) {
      costSheet.getRange(costRow, 1, 1, c.length).setValues([c]);
    } else {
      costSheet.appendRow(c);
    }
  }

  if (cashSheet) {
    var existing = findRowById(cashSheet, 10, c[9]);
    var nextRow = existing > 0 ? existing : Math.max(cashSheet.getLastRow() + 1, 4);
    var cashRow = [
      c[0], c[1], c[3] || 'Biaya HPP', c[2], 'Vendor/Produksi',
      0, c[7],
      '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
      'Kas/Transfer', c[9]
    ];

    if (existing > 0) {
      cashSheet.getRange(existing, 1, 1, cashRow.length).setValues([cashRow]);
    } else {
      cashSheet.appendRow(cashRow);
    }
  }
}

function handleAddPayment(payData, ss) {
  var cashSheet = ss.getSheetByName('Arus Kas');
  if (!cashSheet) return;

  var p = Array.isArray(payData) ? payData : [
    payData.date, payData.trx_no || payData.order_number, payData.desc,
    payData.category || 'Pelunasan', payData.customer,
    payData.amount, 0, '', payData.method, payData.id || ''
  ];

  var existing = findRowById(cashSheet, 10, p[9]);
  var nextRow = existing > 0 ? existing : Math.max(cashSheet.getLastRow() + 1, 4);
  var row = [
    p[0], p[1], p[2] || ('Pembayaran ' + p[1]),
    p[3] || 'Pelunasan', p[4] || '-', p[5] || 0, 0,
    '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
    p[8] || 'Transfer Bank', p[9]
  ];

  if (existing > 0) {
    cashSheet.getRange(existing, 1, 1, row.length).setValues([row]);
  } else {
    cashSheet.appendRow(row);
  }
}

/**
 * Hapus baris yang datanya dihapus di panel admin. Tanpa ini pesanan yang sudah
 * dihapus tetap menyumbang omzet dan laba, dan pembayaran yang dihapus
 * meninggalkan Kas Masuk yang tidak pernah terjadi.
 */
function handleDelete(data, ss) {
  var orderSheet = ss.getSheetByName('Data Order');
  var costSheet  = ss.getSheetByName('Rincian Biaya HPP');
  var cashSheet  = ss.getSheetByName('Arus Kas');

  if (data.entity === 'order') {
    deleteRowById(orderSheet, 15, data.id);

    // Biaya dan pembayaran milik pesanan itu ikut terhapus di database,
    // jadi barisnya harus hilang juga dari Rincian HPP dan Arus Kas.
    var costIds = data.cost_ids || [];
    for (var i = 0; i < costIds.length; i++) {
      deleteRowById(costSheet, 10, costIds[i]);
      deleteRowById(cashSheet, 10, costIds[i]);
    }

    var payIds = data.payment_ids || [];
    for (var j = 0; j < payIds.length; j++) {
      deleteRowById(cashSheet, 10, payIds[j]);
    }
  } else if (data.entity === 'cost') {
    deleteRowById(costSheet, 10, data.id);
    deleteRowById(cashSheet, 10, data.id);
  } else if (data.entity === 'payment') {
    deleteRowById(cashSheet, 10, data.id);
  }

  refreshSaldoFormulas(cashSheet);
}

/** Saldo berjalan bergantung pada nomor baris, jadi ditulis ulang setiap ada baris hilang. */
function refreshSaldoFormulas(sheet) {
  if (!sheet) return;

  var lastRow = sheet.getLastRow();
  if (lastRow < 4) return;

  var formulas = [];
  for (var r = 4; r <= lastRow; r++) {
    formulas.push(['=IF(A' + r + '="","",SUM($F$4:F' + r + ')-SUM($G$4:G' + r + '))']);
  }
  sheet.getRange(4, 8, formulas.length, 1).setFormulas(formulas);
}

/* ========================================================================== */
/* UTILITIES                                                                   */
/* ========================================================================== */
function getOrCreateSheet(ss, name) {
  var s = ss.getSheetByName(name);
  if (!s) s = ss.insertSheet(name);
  return s;
}

/**
 * Jumlah baris data yang tersedia di sebuah tab (seluruh sheet, bukan 300 baris
 * pertama). Format yang dipasang sebatas nomor baris tertentu membuat sheet
 * mendadak polos begitu datanya melewati batas itu.
 */
function dataRowCount(sheet) {
  return Math.max(sheet.getMaxRows() - 3, 1);
}

/** Baris selang-seling memakai banding bawaan Sheets: satu panggilan, tanpa batas baris. */
function applyRowStripes(sheet, numCols) {
  var range = sheet.getRange(4, 1, dataRowCount(sheet), numCols);

  var existing = range.getBandings();
  for (var i = 0; i < existing.length; i++) { existing[i].remove(); }

  range.applyRowBanding(SpreadsheetApp.BandingTheme.LIGHT_GREY, false, false)
    .setFirstRowColor(CLR.rowOdd)
    .setSecondRowColor(CLR.rowEven);

  sheet.setRowHeights(4, dataRowCount(sheet), ROW_H);
}

/**
 * Kolom ID teknis (ORD-1, PAY-9, CST-4). Disembunyikan dari pengguna tapi
 * dipakai untuk menghapus baris yang tepat saat datanya dihapus di panel admin.
 */
function setupIdColumn(sheet, col) {
  sheet.getRange(3, col).setValue('ID')
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center');
  sheet.setColumnWidth(col, 90);
  sheet.hideColumns(col);
}

/** Cari nomor baris berdasarkan nilai di kolom ID. Mengembalikan -1 bila tidak ada. */
function findRowById(sheet, idCol, id) {
  if (!sheet || !id) return -1;

  var lastRow = sheet.getLastRow();
  if (lastRow < 4) return -1;

  var values = sheet.getRange(4, idCol, lastRow - 3, 1).getValues();
  for (var i = 0; i < values.length; i++) {
    if (String(values[i][0]) === String(id)) return i + 4;
  }

  return -1;
}

function deleteRowById(sheet, idCol, id) {
  var row = findRowById(sheet, idCol, id);
  if (row > 0) { sheet.deleteRow(row); return true; }
  return false;
}

function clearSheetDataRows(sheet, startRow) {
  var lastRow = sheet.getLastRow();
  var lastCol = Math.max(sheet.getLastColumn(), 1);
  if (lastRow >= startRow) {
    sheet.getRange(startRow, 1, lastRow - startRow + 1, lastCol).clearContent();
  }
}

function autoFitColumns(sheet, maxCols) {
  for (var c = 1; c <= maxCols; c++) { sheet.autoResizeColumn(c); }
}

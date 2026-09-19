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
var SCRIPT_VERSION = '2026-09-19.2';

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
/* TAB: DASHBOARD                                                              */
/* ========================================================================== */
function setupDashboardSheet(sheet, rekapSheet) {
  sheet.clear();
  var charts = sheet.getCharts();
  for (var i = 0; i < charts.length; i++) { sheet.removeChart(charts[i]); }

  // ── ROW HEIGHTS & COLUMN WIDTHS ──────────────────────────────────────────
  sheet.setColumnWidth(1, 200); // A
  sheet.setColumnWidth(2, 200); // B
  sheet.setColumnWidth(3, 200); // C
  sheet.setColumnWidth(4, 200); // D
  sheet.setColumnWidth(5, 200); // E
  sheet.setColumnWidth(6, 200); // F
  sheet.setColumnWidth(7, 200); // G
  sheet.setColumnWidth(8, 200); // H

  // ── ROW 1: HEADER BANNER ─────────────────────────────────────────────────
  sheet.setRowHeight(1, 50);
  var r1 = sheet.getRange('A1:H1');
  r1.merge()
    .setValue('📊  DASHBOARD KEUANGAN & OPERASIONAL  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial')
    .setFontSize(14).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // Decorative bar under header
  sheet.setRowHeight(2, 5);
  sheet.getRange('A2:H2').setBackground('#F59E0B');

  // ── ROW 3–4: KPI CARDS ROW 1 ─────────────────────────────────────────────
  sheet.setRowHeight(3, 20);
  sheet.setRowHeight(4, 45);

  var kpi1 = [
    {
      cL: 'A', cR: 'B',
      label: '💰  Saldo Kas Saat Ini',
      formula: "=SUM('Arus Kas'!F4:F)-SUM('Arus Kas'!G4:G)",
      bg: CLR.blueCard, textColor: CLR.blue, fmt: '"Rp"#,##0'
    },
    {
      cL: 'C', cR: 'D',
      label: '⬆  Kas Masuk Bulan Ini',
      formula: "=SUMIFS('Arus Kas'!F4:F,'Arus Kas'!A4:A,\">=\"&DATE(YEAR(TODAY()),MONTH(TODAY()),1),'Arus Kas'!A4:A,\"<=\"&EOMONTH(TODAY(),0))",
      bg: CLR.greenCard, textColor: CLR.green, fmt: '"Rp"#,##0'
    },
    {
      cL: 'E', cR: 'F',
      label: '⬇  Kas Keluar Bulan Ini',
      formula: "=SUMIFS('Arus Kas'!G4:G,'Arus Kas'!A4:A,\">=\"&DATE(YEAR(TODAY()),MONTH(TODAY()),1),'Arus Kas'!A4:A,\"<=\"&EOMONTH(TODAY(),0))",
      bg: CLR.redCard, textColor: CLR.red, fmt: '"Rp"#,##0'
    },
    {
      cL: 'G', cR: 'H',
      label: '📈  Arus Kas Bersih',
      formula: '=C4-E4',
      bg: CLR.slateCard, textColor: CLR.slate, fmt: '"Rp"#,##0'
    }
  ];

  for (var i = 0; i < kpi1.length; i++) {
    var k = kpi1[i];
    sheet.getRange(k.cL + '3:' + k.cR + '3').merge()
      .setValue(k.label)
      .setFontSize(8).setFontWeight('bold').setFontColor(CLR.gray2).setBackground(k.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle');
    sheet.getRange(k.cL + '4:' + k.cR + '4').merge()
      .setFormula(k.formula)
      .setFontSize(16).setFontWeight('bold').setFontColor(k.textColor).setBackground(k.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle')
      .setNumberFormat(k.fmt);
    // Bottom border accent
    sheet.getRange(k.cL + '4:' + k.cR + '4')
      .setBorder(null, null, true, null, null, null, k.textColor, SpreadsheetApp.BorderStyle.SOLID_THICK);
  }

  // ── ROW 5: GAP ───────────────────────────────────────────────────────────
  sheet.setRowHeight(5, 10);

  // ── ROW 6–7: KPI CARDS ROW 2 ─────────────────────────────────────────────
  sheet.setRowHeight(6, 20);
  sheet.setRowHeight(7, 45);

  var kpi2 = [
    {
      cL: 'A', cR: 'B',
      label: '⏳  Total Sisa Piutang',
      formula: "=IFERROR(SUM('Data Order'!L4:L303),0)",
      bg: CLR.amberCard, textColor: CLR.amber, fmt: '"Rp"#,##0'
    },
    {
      cL: 'C', cR: 'D',
      label: '🛍  Total Nilai Order',
      formula: "=IFERROR(SUM('Data Order'!G4:G303),0)",
      bg: CLR.blueCard, textColor: CLR.blue, fmt: '"Rp"#,##0'
    },
    {
      cL: 'E', cR: 'F',
      label: '✅  Estimasi Laba Bersih',
      formula: "=IFERROR(SUM('Data Order'!K4:K303),0)",
      bg: CLR.greenCard, textColor: CLR.green, fmt: '"Rp"#,##0'
    },
    {
      cL: 'G', cR: 'H',
      label: '📊  Margin Keuntungan',
      formula: "=IFERROR(IF(D7>0,F7/D7,0),0)",
      bg: CLR.purpleCard, textColor: CLR.purple, fmt: '0.0%'
    }
  ];

  for (var j = 0; j < kpi2.length; j++) {
    var k2 = kpi2[j];
    sheet.getRange(k2.cL + '6:' + k2.cR + '6').merge()
      .setValue(k2.label)
      .setFontSize(8).setFontWeight('bold').setFontColor(CLR.gray2).setBackground(k2.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle');
    sheet.getRange(k2.cL + '7:' + k2.cR + '7').merge()
      .setFormula(k2.formula)
      .setFontSize(16).setFontWeight('bold').setFontColor(k2.textColor).setBackground(k2.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle')
      .setNumberFormat(k2.fmt);
    sheet.getRange(k2.cL + '7:' + k2.cR + '7')
      .setBorder(null, null, true, null, null, null, k2.textColor, SpreadsheetApp.BorderStyle.SOLID_THICK);
  }

  // ── ROW 8: GAP ───────────────────────────────────────────────────────────
  sheet.setRowHeight(8, 14);

  // ── ROW 9: HPP SECTION HEADER ────────────────────────────────────────────
  sheet.setRowHeight(9, 28);
  sheet.getRange('A9:D9').merge()
    .setValue('⚙  KOMPOSISI BIAYA PRODUKSI (HPP)')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('left').setVerticalAlignment('middle')
    .setWrap(false);

  // ── ROW 10: HPP TABLE HEADER ─────────────────────────────────────────────
  sheet.setRowHeight(10, 22);
  var hppHdr = ['Kategori HPP', 'Keterangan', 'Total Biaya', '% Porsi'];
  sheet.getRange(10, 1, 1, 4).setValues([hppHdr])
    .setFontSize(8).setFontWeight('bold').setFontColor(CLR.navy)
    .setBackground(CLR.blueLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ── ROWS 11–15: HPP CATEGORIES ───────────────────────────────────────────
  var cats = [
    ['Kain & Bahan Baku',     'Kain utama, rib, furing, kerah'],
    ['Upah CMT / Jahit',      'Upah jahit, cutting, pola, finishing'],
    ['Jasa Makloon',          'Bordir, sablon DTF, plastisol'],
    ['Aksesoris & Trims',     'Kancing, resleting, label woven, hangtag'],
    ['Packing & Operasional', 'Plastik, lakban, kurir, operasional']
  ];

  for (var k = 0; k < cats.length; k++) {
    var rr = 11 + k;
    sheet.setRowHeight(rr, 20);
    var rowBg = (k % 2 === 0) ? CLR.rowOdd : CLR.rowEven;
    sheet.getRange(rr, 1).setValue(cats[k][0])
      .setFontSize(8).setFontWeight('bold').setBackground(rowBg);
    sheet.getRange(rr, 2).setValue(cats[k][1])
      .setFontSize(8).setFontColor(CLR.gray2).setBackground(rowBg);
    sheet.getRange(rr, 3)
      .setFormula("=IFERROR(SUMIF('Rincian Biaya HPP'!C4:C500,\"" + cats[k][0] + "\",'Rincian Biaya HPP'!H4:H500),0)")
      .setFontSize(8).setFontWeight('bold').setBackground(rowBg).setNumberFormat('"Rp"#,##0');
    sheet.getRange(rr, 4)
      .setFormula('=IFERROR(IF(C16>0,C' + rr + '/C16,0),0)')
      .setFontSize(8).setBackground(rowBg).setNumberFormat('0.0%');
  }

  // ── ROW 16: TOTAL HPP ────────────────────────────────────────────────────
  sheet.setRowHeight(16, 22);
  sheet.getRange('A16:B16').merge().setValue('TOTAL BIAYA HPP')
    .setFontSize(8).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setHorizontalAlignment('right');
  sheet.getRange('C16')
    .setFormula('=SUM(C11:C15)')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setNumberFormat('"Rp"#,##0');
  sheet.getRange('D16')
    .setFormula('=IFERROR(C16/D7,0)')
    .setFontSize(8).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setNumberFormat('0.0%');

  // ── ROW 17: UPDATE TIMESTAMP ──────────────────────────────────────────────
  sheet.setRowHeight(17, 18);
  sheet.getRange('A17:H17').merge()
    .setFormula('="⏱ Data terakhir diperbarui: "&TEXT(NOW(),"dd mmmm yyyy, HH:mm") & " WIB"')
    .setFontSize(8).setFontColor(CLR.gray2).setBackground(CLR.gray1)
    .setHorizontalAlignment('right').setVerticalAlignment('middle');

  // ── CHART: Tren Arus Kas Bulanan ─────────────────────────────────────────
  if (rekapSheet) {
    try {
      var chart = sheet.newChart()
        .asColumnChart()
        .addRange(rekapSheet.getRange('A3:C15'))
        .setPosition(9, 5, 5, 0)
        .setOption('title', 'Tren Arus Kas Masuk vs Keluar Bulanan (2026)')
        .setOption('titleTextStyle', { fontSize: 11, bold: true, color: CLR.navy })
        .setOption('legend', { position: 'top', textStyle: { fontSize: 9 } })
        .setOption('colors', ['#1A6B36', '#B22222'])
        .setOption('backgroundColor', { fill: '#F7FAFF' })
        .setOption('chartArea', { left: 60, top: 40, width: '80%', height: '72%' })
        .setOption('hAxis', { textStyle: { fontSize: 8 } })
        .setOption('vAxis', {
          textStyle: { fontSize: 8 },
          format: '"Rp"#,##0'
        })
        .setOption('width', 530)
        .setOption('height', 270)
        .build();
      sheet.insertChart(chart);
    } catch(e) {}
  }
}

/* ========================================================================== */
/* TAB: DATA ORDER                                                             */
/* ========================================================================== */
function setupDataOrderSheet(sheet) {
  sheet.clear();

  // Column widths — fine-tuned for readability
  sheet.setColumnWidth(1, 90);   // A: Tanggal
  sheet.setColumnWidth(2, 135);  // B: No. Order
  sheet.setColumnWidth(3, 120);  // C: Customer
  sheet.setColumnWidth(4, 180);  // D: Produk
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
  sheet.setColumnWidth(3, 230);  // C: Keterangan
  sheet.setColumnWidth(4, 110);  // D: Kategori
  sheet.setColumnWidth(5, 145);  // E: Customer/Supplier
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
  sheet.getRange(4, 6, rows, 3).setNumberFormat('"Rp"#,##0');

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
  sheet.setColumnWidth(3, 140);  // C: Kategori
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

  for (var m = 1; m <= 12; m++) {
    var r = 3 + m;
    rows.push([
      '=DATE(' + yr + ',' + m + ',1)',
      "=SUMIFS('Arus Kas'!$F$4:$F$503,'Arus Kas'!$A$4:$A$503,\">=\"&A" + r + ",'Arus Kas'!$A$4:$A$503,\"<\"&EDATE(A" + r + ",1))",
      "=SUMIFS('Arus Kas'!$G$4:$G$503,'Arus Kas'!$A$4:$A$503,\">=\"&A" + r + ",'Arus Kas'!$A$4:$A$503,\"<\"&EDATE(A" + r + ",1))",
      '=B' + r + '-C' + r,
      "=SUMIFS('Data Order'!$G$4:$G$303,'Data Order'!$A$4:$A$303,\">=\"&A" + r + ",'Data Order'!$A$4:$A$303,\"<\"&EDATE(A" + r + ",1))",
      "=SUMIFS('Data Order'!$K$4:$K$303,'Data Order'!$A$4:$A$303,\">=\"&A" + r + ",'Data Order'!$A$4:$A$303,\"<\"&EDATE(A" + r + ",1))",
      "=SUMIFS('Data Order'!$L$4:$L$303,'Data Order'!$A$4:$A$303,\">=\"&A" + r + ",'Data Order'!$A$4:$A$303,\"<\"&EDATE(A" + r + ",1))"
    ]);
  }

  sheet.getRange(4, 1, rows.length, hdrs.length).setFormulas(rows);
  sheet.getRange('A4:A15').setNumberFormat('mmmm yyyy');
  sheet.getRange('B4:G15').setNumberFormat('"Rp"#,##0');

  // Alternating row colors
  for (var ri = 4; ri <= 15; ri++) {
    var bg = (ri % 2 === 0) ? '#EBF3FB' : CLR.rowOdd;
    sheet.setRowHeight(ri, 22);
    sheet.getRange(ri, 1, 1, 7).setBackground(bg);
    sheet.getRange(ri, 1).setFontWeight('bold');
    // Color-code Arus Kas Bersih (Col D)
    sheet.getRange(ri, 4).setFontColor(CLR.green);
    // Highlight non-zero months with slightly stronger blue
  }

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
          desc:    (c.description || 'Biaya HPP') + ' (' + (c.order_number || '') + ')',
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
      c[0], c[1], c[3] + ' (' + c[1] + ')', c[2], 'Vendor/Produksi',
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

  sheet.setRowHeights(4, dataRowCount(sheet), 20);
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

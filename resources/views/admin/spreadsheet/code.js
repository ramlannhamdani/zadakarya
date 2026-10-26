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
var SCRIPT_VERSION = '2026-10-26.06';

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
    } else if (data.type === 'expense' || data.action === 'add_expense') {
      handleAddExpense(data.expense || data.row, ss);
    } else if (data.type === 'loan' || data.action === 'upsert_loan') {
      handleUpsertLoan(data.loan || data.row, ss);
    } else if (data.type === 'loan_payment' || data.action === 'add_loan_payment') {
      handleAddLoanPayment(data.loan_payment || data.row, ss);
    } else if (data.type === 'asset' || data.action === 'upsert_asset') {
      handleUpsertAsset(data.asset || data.row, ss);
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

  var dashSheet       = getOrCreateSheet(ss, 'Dashboard');
  var neracaSheet     = getOrCreateSheet(ss, 'Neraca');
  var labaRugiSheet   = getOrCreateSheet(ss, 'Laba Rugi');
  var asetSheet       = getOrCreateSheet(ss, 'Aset');
  var orderSheet      = getOrCreateSheet(ss, 'Data Order');
  var cashSheet       = getOrCreateSheet(ss, 'Arus Kas');
  var pinjamanSheet   = getOrCreateSheet(ss, 'Pinjaman');
  var costSheet       = getOrCreateSheet(ss, 'Rincian Biaya HPP');
  var convectionSheet = getOrCreateSheet(ss, 'Beban Konveksi');
  var personalSheet   = getOrCreateSheet(ss, 'Beban Pribadi');
  var rekapSheet      = getOrCreateSheet(ss, 'Rekap Bulanan');

  setupAsetSheet(asetSheet);
  setupLabaRugiSheet(labaRugiSheet);
  setupNeracaSheet(neracaSheet);
  setupArusKasSheet(cashSheet);
  setupDataOrderSheet(orderSheet);
  setupPinjamanSheet(pinjamanSheet);
  setupRincianHppSheet(costSheet);
  setupBebanKonveksiSheet(convectionSheet);
  setupBebanPribadiSheet(personalSheet);
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

  // Tata urutan tab agar runut dan mudah dibaca
  var tabOrder = [
    'Dashboard', 'Neraca', 'Laba Rugi', 'Aset', 'Data Order', 'Arus Kas', 'Pinjaman',
    'Rincian Biaya HPP', 'Beban Konveksi', 'Beban Pribadi',
    'Rekap Bulanan'
  ];
  for (var t = 0; t < tabOrder.length; t++) {
    var s = ss.getSheetByName(tabOrder[t]);
    if (s) {
      try { ss.setActiveSheet(s); ss.moveActiveSheet(t + 1); } catch(e) {}
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
 * Siapkan tempat grafik arus kas di Dashboard.
 *
 * Grafik TIDAK dibuat dari kode. Beberapa kali percobaan lewat
 * EmbeddedChartBuilder hanya menghasilkan kotak berisi judul tanpa satu pun
 * seri, sementara grafik yang sama dibuat lewat menu Insert > Chart langsung
 * berhasil. Karena skrip Apps Script tidak bisa dijalankan di lingkungan
 * pengembangan, menebak sebabnya lebih mahal daripada membuatnya sekali secara
 * manual — dan grafik buatan tangan justru lebih mudah diatur pemiliknya.
 *
 * Yang dilakukan di sini: menjaga barisnya tetap tersedia, dan menampilkan
 * petunjuk selama grafiknya belum ada. Begitu grafik dipasang, petunjuknya
 * hilang sendiri dan tidak pernah ditimpa oleh "Tata Ulang Dashboard".
 */
function buildCashChart(ss) {
  var sheet = ss.getSheetByName('Dashboard');
  if (!sheet) { return; }

  var hint = sheet.getRange('A36:H37');
  hint.breakApart(); // sel gabungan dari pemasangan sebelumnya harus dilepas dulu

  if (sheet.getCharts().length > 0) {
    hint.clearContent().setBackground(null).setBorder(false, false, false, false, false, false);
    return;
  }

  hint.merge()
    .setValue('Grafik arus kas bulanan belum dipasang.\n'
            + 'Buka tab "Rekap Bulanan", blok A3:C15, lalu Insert > Chart. '
            + 'Geser grafiknya ke area ini. Grafik yang Anda pasang tidak akan terhapus '
            + 'saat menekan "Tata Ulang Dashboard".')
    .setFontSize(10).setFontColor(CLR.gray2).setBackground(CLR.gray1)
    .setHorizontalAlignment('center').setVerticalAlignment('middle').setWrap(true)
    .setBorder(true, true, true, true, false, false, CLR.border, SpreadsheetApp.BorderStyle.DASHED);
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
  // clear() menghapus isi dan format, TIDAK menghapus grafik — dan memang
  // jangan sampai menghapusnya: grafik arus kas dipasang manual oleh pemilik
  // spreadsheet, jadi ia harus selamat setiap kali tampilan ditata ulang.
  sheet.clear();

  // Delapan kolom selebar sama supaya kartu KPI yang menggabung dua kolom
  for (var c = 1; c <= 8; c++) { sheet.setColumnWidth(c, 175); }
  sheet.setColumnWidth(5, 195);
  sheet.setColumnWidth(6, 155);

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
    },
    {
      labelRow: 9, valueRow: 10,
      cards: [
        { col: 'A', label: 'Total Biaya HPP (Produksi)',
          formula: "=IFERROR(SUM('Rincian Biaya HPP'!$H$4:$H),0)",
          bg: CLR.redCard, fg: CLR.red, fmt: '"Rp"#,##0' },
        { col: 'C', label: 'Beban Kantor & Konveksi',
          formula: "=IFERROR(SUM('Beban Konveksi'!$E$4:$E),0)",
          bg: CLR.redCard, fg: CLR.red, fmt: '"Rp"#,##0' },
        { col: 'E', label: 'Beban Pribadi (Prive Owner)',
          formula: "=IFERROR(SUM('Beban Pribadi'!$E$4:$E),0)",
          bg: CLR.purpleCard, fg: CLR.purple, fmt: '"Rp"#,##0' },
        { col: 'G', label: 'Talangan Kas (Nombok DP)',
          formula: "=IFERROR(SUM('Data Order'!$M$4:$M),0)",
          bg: CLR.amberCard, fg: CLR.amber, fmt: '"Rp"#,##0' }
      ]
    },
    {
      labelRow: 12, valueRow: 13,
      cards: [
        { col: 'A', label: 'Sisa Pinjaman (Pihak Luar)',
          formula: "=IFERROR(SUM('Pinjaman'!$F$4:$F),0)",
          bg: CLR.purpleCard, fg: CLR.purple, fmt: '"Rp"#,##0' },
        { col: 'C', label: 'Piutang Selesai (Siap Tagih)',
          formula: "=IFERROR(SUMIF('Data Order'!$N$4:$N,\"*Selesai*\",'Data Order'!$L$4:$L),0)",
          bg: CLR.redCard, fg: CLR.red, fmt: '"Rp"#,##0' },
        { col: 'E', label: 'Piutang Berjalan (WIP)',
          formula: "=IFERROR(SUMIF('Data Order'!$N$4:$N,\"*Aktif*\",'Data Order'!$L$4:$L),0)",
          bg: CLR.blueCard, fg: CLR.blue, fmt: '"Rp"#,##0' },
        { col: 'G', label: 'Laba Bersih Operasional',
          formula: '=IFERROR(E7-C10,0)',
          bg: CLR.greenCard, fg: CLR.green, fmt: '"Rp"#,##0;[Red]-"Rp"#,##0' }
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
  sheet.setRowHeight(8, 12);
  sheet.setRowHeight(11, 12);
  sheet.setRowHeight(14, 18);

  /* ── ROW 15–22: KOMPOSISI HPP (kiri) & RINGKASAN PESANAN (kanan) ────────── */

  sheet.setRowHeight(15, 26);
  sheet.getRange('A15:D15').merge()
    .setValue('KOMPOSISI BIAYA PRODUKSI (HPP)  —  terbesar di atas')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(false);
  sheet.getRange('E15:H15').merge()
    .setValue('STRUKTUR PENGELUARAN KAS (HPP vs OPS. KONVEKSI vs PRIVE)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(false);

  sheet.setRowHeight(16, 24);
  sheet.getRange(16, 1, 1, 4).setValues([['Kategori HPP', 'Total Biaya', '% Porsi', 'Perbandingan']])
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.navy).setBackground(CLR.blueLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  sheet.getRange(16, 5, 1, 4).setValues([['Jenis Pengeluaran Kas', 'Bulan Ini', 'Total Kas', '% Porsi']])
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.navy).setBackground(CLR.blueLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // Kategori dibaca langsung dari datanya dan diurutkan dari biaya terbesar,
  // bukan dari daftar tetap di dalam kode. Selain menjawab "biaya paling besar
  // apa", ini juga menghapus seluruh kemungkinan label meleset — dulu "Jasa
  // Makloon" tidak cocok dengan label Laravel dan diam-diam tampil Rp0.
  // A17 meluber ke A17:B21; JANGAN isi sel di rentang itu atau rumusnya gagal.
  sheet.getRange('A17').setFormula(
    '=IFERROR(QUERY(\'Rincian Biaya HPP\'!A4:I,'
    + '"select C, sum(H) where C is not null group by C order by sum(H) desc limit 5 label sum(H) \'\'",0),"")'
  );

  for (var r = 17; r <= 21; r++) {
    sheet.setRowHeight(r, ROW_H);
    var rowBg = (r % 2 === 1) ? CLR.rowOdd : CLR.rowEven;
    sheet.getRange(r, 1, 1, 4).setBackground(rowBg).setVerticalAlignment('middle');

    sheet.getRange(r, 1).setFontSize(9).setFontWeight('bold').setWrap(false);
    sheet.getRange(r, 2).setFontSize(9).setFontWeight('bold').setNumberFormat('"Rp"#,##0');
    sheet.getRange(r, 3)
      .setFormula('=IFERROR(IF($B$22>0,B' + r + '/$B$22,""),"")')
      .setFontSize(9).setNumberFormat('0.0%').setHorizontalAlignment('center');
    sheet.getRange(r, 4)
      .setFormula('=IFERROR(SPARKLINE(B' + r + ',{"charttype","bar";"max",MAX($B$17:$B$21);"color1","' + CLR.blue + '"}),"")');
  }

  sheet.setRowHeight(22, 26);
  sheet.getRange('A22').setValue('TOTAL BIAYA HPP')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setHorizontalAlignment('right').setVerticalAlignment('middle');
  sheet.getRange('B22').setFormula('=IFERROR(SUM(B17:B21),0)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setNumberFormat('"Rp"#,##0');
  sheet.getRange('C22').setFormula('=IFERROR(SUM(C17:C21),0)')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white)
    .setBackground(CLR.blue).setNumberFormat('0.0%').setHorizontalAlignment('center');
  sheet.getRange('D22').setBackground(CLR.blue);

  // Rincian Pengeluaran Kas (Baris 17: HPP, 18: Ops. Konveksi, 19: Ops. Pribadi, 20: Talangan Nombok, 21: Total Pengeluaran Kas)
  var outflowRows = [
    [
      '1. HPP Produksi (Biaya Order)',
      '=IFERROR(SUMIFS(\'Rincian Biaya HPP\'!$H$4:$H,\'Rincian Biaya HPP\'!$A$4:$A,">="&EOMONTH(TODAY(),-1)+1,\'Rincian Biaya HPP\'!$A$4:$A,"<="&EOMONTH(TODAY(),0)),0)',
      '=IFERROR(SUM(\'Rincian Biaya HPP\'!$H$4:$H),0)',
      '=IFERROR(IF($G$21>0,G17/$G$21,0),0)'
    ],
    [
      '2. Beban Kantor & Konveksi',
      '=IFERROR(SUMIFS(\'Beban Konveksi\'!$E$4:$E,\'Beban Konveksi\'!$A$4:$A,">="&EOMONTH(TODAY(),-1)+1,\'Beban Konveksi\'!$A$4:$A,"<="&EOMONTH(TODAY(),0)),0)',
      '=IFERROR(SUM(\'Beban Konveksi\'!$E$4:$E),0)',
      '=IFERROR(IF($G$21>0,G18/$G$21,0),0)'
    ],
    [
      '3. Beban Pribadi (Prive Owner)',
      '=IFERROR(SUMIFS(\'Beban Pribadi\'!$E$4:$E,\'Beban Pribadi\'!$A$4:$A,">="&EOMONTH(TODAY(),-1)+1,\'Beban Pribadi\'!$A$4:$A,"<="&EOMONTH(TODAY(),0)),0)',
      '=IFERROR(SUM(\'Beban Pribadi\'!$E$4:$E),0)',
      '=IFERROR(IF($G$21>0,G19/$G$21,0),0)'
    ],
    [
      '↳ Talangan Kas (Nombok DP)',
      'Kas talangi DP',
      '=IFERROR(SUM(\'Data Order\'!$M$4:$M),0)',
      'Kas Kantor'
    ]
  ];

  for (var oi = 0; oi < outflowRows.length; oi++) {
    var orow = 17 + oi;
    var oBg = (orow % 2 === 1) ? CLR.rowOdd : CLR.rowEven;
    sheet.getRange(orow, 5, 1, 4).setBackground(oBg).setVerticalAlignment('middle');

    sheet.getRange('E' + orow).setValue(outflowRows[oi][0])
      .setFontSize(9).setFontWeight('bold').setFontColor(CLR.slate).setVerticalAlignment('middle');

    if (oi === 3) {
      sheet.getRange('F' + orow).setValue(outflowRows[oi][1])
        .setFontSize(8).setFontColor(CLR.gray2).setHorizontalAlignment('center');
      sheet.getRange('G' + orow).setFormula(outflowRows[oi][2])
        .setFontSize(9).setFontWeight('bold').setFontColor(CLR.amber).setNumberFormat('"Rp"#,##0');
      sheet.getRange('H' + orow).setValue(outflowRows[oi][3])
        .setFontSize(8).setFontColor(CLR.gray2).setHorizontalAlignment('center');
    } else {
      sheet.getRange('F' + orow).setFormula(outflowRows[oi][1])
        .setFontSize(9).setFontWeight('bold').setNumberFormat('"Rp"#,##0');
      sheet.getRange('G' + orow).setFormula(outflowRows[oi][2])
        .setFontSize(9).setFontWeight('bold').setNumberFormat('"Rp"#,##0');
      sheet.getRange('H' + orow).setFormula(outflowRows[oi][3])
        .setFontSize(9).setFontWeight('bold').setNumberFormat('0.0%').setHorizontalAlignment('center');
    }
  }

  // Row 21: TOTAL PENGELUARAN KAS
  sheet.getRange(21, 5, 1, 4).setBackground(CLR.navy).setVerticalAlignment('middle');
  sheet.getRange('E21').setValue('TOTAL PENGELUARAN KAS')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white).setHorizontalAlignment('right');
  sheet.getRange('F21').setFormula('=IFERROR(SUM(F17:F19),0)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('"Rp"#,##0');
  sheet.getRange('G21').setFormula('=IFERROR(SUM(\'Arus Kas\'!$G$4:$G),0)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('"Rp"#,##0');
  sheet.getRange('H21').setFormula('=IFERROR(IF(G21>0,SUM(H17:H19),0),0)')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('0.0%').setHorizontalAlignment('center');

  // Row 22: Penjelasan akuntansi kas keluar
  sheet.getRange('E22:H22').merge()
    .setValue('Catatan: Rincian biaya dicatat di tab "Rincian Biaya HPP", "Beban Konveksi", & "Beban Pribadi". Total mutasi di "Arus Kas".')
    .setFontSize(8).setFontColor(CLR.gray2).setBackground(CLR.gray1)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  /* ── ROW 24–33: MARGIN PER PESANAN ─────────────────────────────────────── */

  sheet.setRowHeight(23, 14);
  sheet.setRowHeight(24, 26);
  sheet.getRange('A24:H24').merge()
    .setValue('MARGIN PER PESANAN  —  margin terbesar di atas')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.green)
    .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(false);

  sheet.setRowHeight(25, 24);
  sheet.getRange(25, 1, 1, 6)
    .setValues([['No. Order', 'Customer', 'Nilai Order', 'Total HPP', 'Estimasi Laba', 'Margin']])
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.green).setBackground(CLR.greenLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.getRange('G25:H25').merge().setValue('Perbandingan Margin')
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.green).setBackground(CLR.greenLight)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // Pesanan tanpa nilai order dilewati supaya margin tidak dibagi nol.
  // A26 meluber ke A26:F33; jangan isi sel di rentang itu.
  sheet.getRange('A26').setFormula(
    '=IFERROR(QUERY(\'Data Order\'!A4:N,'
    + '"select B, C, G, J, K, K/G where B is not null and G > 0 order by K/G desc limit 8 label K/G \'\'",0),"")'
  );

  for (var mr = 26; mr <= 33; mr++) {
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
        + '{"charttype","bar";"max",MAX($F$26:$F$33);"color1","' + CLR.green + '"})),"")');
  }

  /* ── ROW 35–46: GRAFIK ─────────────────────────────────────────────────── */
  // Grafik melayang di atas sel, jadi barisnya disediakan setinggi grafiknya.
  sheet.setRowHeight(34, 14);
  for (var gr = 35; gr <= 46; gr++) { sheet.setRowHeight(gr, ROW_H); }

  buildCashChart(sheet.getParent());

  /* ── ROW 48: KETERANGAN WAKTU ──────────────────────────────────────────── */
  sheet.setRowHeight(47, 10);
  sheet.setRowHeight(48, 22);
  sheet.getRange('A48:H48').merge()
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
  sheet.setColumnWidth(13, 125); // M: Dana Talangan
  sheet.setColumnWidth(14, 140); // N: Status
  sheet.setColumnWidth(15, 200); // O: Catatan

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:O1').merge()
    .setValue('🛒  DATA ORDER & PROFITABILITAS  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:O2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var headers = [
    'Tanggal', 'No. Order', 'Customer', 'Nama Produk / Item', 'Qty',
    'Harga / pcs', 'Nilai Order', 'DP Diterima', 'Pelunasan',
    'Total HPP', 'Estimasi Laba', 'Sisa Tagihan', 'Dana Talangan', 'Status', 'Catatan'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 16);

  // Format dipasang untuk seluruh kolom, bukan sampai baris tertentu.
  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 5, rows, 1).setNumberFormat('#,##0').setHorizontalAlignment('center');
  sheet.getRange(4, 6, rows, 7).setNumberFormat('"Rp"#,##0');
  // Estimasi laba bisa minus kalau HPP melampaui nilai order.
  sheet.getRange(4, 11, rows, 1).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  sheet.getRange(4, 13, rows, 1).setNumberFormat('"Rp"#,##0'); // Dana Talangan
  sheet.getRange(4, 14, rows, 1).setHorizontalAlignment('center').setWrap(false); // Status
  sheet.getRange(4, 15, rows, 1).setWrap(true); // Catatan

  applyRowStripes(sheet, 16);

  // Sorotan visual: Dana Talangan jika > 0, dan Status jika Selesai
  try {
    var talanganRange = sheet.getRange(4, 13, rows, 1);
    var statusRange = sheet.getRange(4, 14, rows, 1);
    var ruleTalangan = SpreadsheetApp.newConditionalFormatRule()
      .whenNumberGreaterThan(0)
      .setBackground(CLR.redCard)
      .setFontColor(CLR.red)
      .setBold(true)
      .setRanges([talanganRange])
      .build();
    var ruleSelesai = SpreadsheetApp.newConditionalFormatRule()
      .whenTextContains('Selesai')
      .setBackground(CLR.greenCard)
      .setFontColor(CLR.green)
      .setBold(true)
      .setRanges([statusRange])
      .build();
    sheet.setConditionalFormatRules([ruleTalangan, ruleSelesai]);
  } catch (e) {}
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

  // Pembeda visual otomatis antara Ops. Konveksi (bisnis) dan Ops. Pribadi (prive owner)
  try {
    var catRange = sheet.getRange(4, 4, rows, 1);
    var rulePribadi = SpreadsheetApp.newConditionalFormatRule()
      .whenTextContains('Ops. Pribadi')
      .setBackground('#FDF2F8')
      .setFontColor('#9D174D')
      .setBold(true)
      .setRanges([catRange])
      .build();
    var ruleKonveksi = SpreadsheetApp.newConditionalFormatRule()
      .whenTextContains('Ops. Konveksi')
      .setBackground('#EEF2FF')
      .setFontColor('#3730A3')
      .setRanges([catRange])
      .build();
    sheet.setConditionalFormatRules([rulePribadi, ruleKonveksi]);
  } catch (e) {}
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
/* TAB: BEBAN KONVEKSI & KANTOR (OVERHEAD)                                     */
/* ========================================================================== */
function setupBebanKonveksiSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 85);   // A: Tanggal
  sheet.setColumnWidth(2, 140);  // B: No. Transaksi
  sheet.setColumnWidth(3, 200);  // C: Kategori Beban
  sheet.setColumnWidth(4, 260);  // D: Keterangan / Keperluan
  sheet.setColumnWidth(5, 125);  // E: Jumlah Pengeluaran
  sheet.setColumnWidth(6, 120);  // F: Metode Bayar
  sheet.setColumnWidth(7, 130);  // G: Dicatat Oleh
  sheet.setColumnWidth(8, 200);  // H: Catatan

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:H1').merge()
    .setValue('🏭  PENGELUARAN KONVEKSI & KANTOR (OVERHEAD)  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#1F3864')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:H2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Tanggal', 'No. Transaksi', 'Kategori Beban', 'Keterangan / Keperluan',
    'Jumlah Pengeluaran', 'Metode Bayar', 'Dicatat Oleh', 'Catatan'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 9);

  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 5, rows, 1).setNumberFormat('"Rp"#,##0');

  applyRowStripes(sheet, 9);
}

/* ========================================================================== */
/* TAB: BEBAN PRIBADI & PRIVE OWNER                                            */
/* ========================================================================== */
function setupBebanPribadiSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 85);   // A: Tanggal
  sheet.setColumnWidth(2, 140);  // B: No. Transaksi
  sheet.setColumnWidth(3, 200);  // C: Kategori Penarikan
  sheet.setColumnWidth(4, 260);  // D: Keterangan / Keperluan
  sheet.setColumnWidth(5, 125);  // E: Jumlah Penarikan (Prive)
  sheet.setColumnWidth(6, 120);  // F: Metode Bayar
  sheet.setColumnWidth(7, 130);  // G: Dicatat Oleh
  sheet.setColumnWidth(8, 200);  // H: Catatan

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:H1').merge()
    .setValue('👤  PENGELUARAN PRIBADI & PRIVE OWNER  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#881337')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:H2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Tanggal', 'No. Transaksi', 'Kategori Penarikan', 'Keterangan / Keperluan',
    'Jumlah Penarikan (Prive)', 'Metode Bayar', 'Dicatat Oleh', 'Catatan'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#9F1239')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 9);

  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 5, rows, 1).setNumberFormat('"Rp"#,##0');

  applyRowStripes(sheet, 9);
}

/* ========================================================================== */
/* TAB: PINJAMAN PIHAK LUAR                                                    */
/* ========================================================================== */
function setupPinjamanSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 95);   // A: Tanggal
  sheet.setColumnWidth(2, 140);  // B: No. Pinjaman
  sheet.setColumnWidth(3, 190);  // C: Pemberi Pinjaman / Kreditur
  sheet.setColumnWidth(4, 130);  // D: Nominal Pinjaman
  sheet.setColumnWidth(5, 120);  // E: Total Dicicil
  sheet.setColumnWidth(6, 130);  // F: Sisa Utang
  sheet.setColumnWidth(7, 100);  // G: Jatuh Tempo
  sheet.setColumnWidth(8, 120);  // H: Status
  sheet.setColumnWidth(9, 140);  // I: Pesanan Terkait
  sheet.setColumnWidth(10, 220); // J: Keperluan / Catatan

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:J1').merge()
    .setValue('🤝  PINJAMAN PIHAK LUAR (MODAL & LIABILITAS)  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#1E1B4B')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:J2').setBackground('#6366F1');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Tanggal', 'No. Pinjaman', 'Pemberi Pinjaman / Kreditur', 'Nominal Pinjaman',
    'Total Dicicil', 'Sisa Utang', 'Jatuh Tempo', 'Status', 'Pesanan Terkait', 'Keperluan / Catatan'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#312E81')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 11);

  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 4, rows, 3).setNumberFormat('"Rp"#,##0');
  sheet.getRange(4, 7, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 8, rows, 1).setHorizontalAlignment('center');

  applyRowStripes(sheet, 11);
}

/* ========================================================================== */
/* TAB: REKAP BULANAN                                                         */
/* ========================================================================== */
function setupRekapBulananSheet(sheet) {
  sheet.clear();

  // Column widths — wider for currency readability
  sheet.setColumnWidth(1, 130);  // A: Bulan
  sheet.setColumnWidth(2, 140);  // B: Kas Masuk
  sheet.setColumnWidth(3, 140);  // C: Total Kas Keluar
  sheet.setColumnWidth(4, 135);  // D: ↳ HPP Produksi
  sheet.setColumnWidth(5, 135);  // E: ↳ Ops. Konveksi
  sheet.setColumnWidth(6, 135);  // F: ↳ Ops. Pribadi (Prive)
  sheet.setColumnWidth(7, 140);  // G: Arus Kas Bersih
  sheet.setColumnWidth(8, 140);  // H: Total Nilai Order
  sheet.setColumnWidth(9, 140);  // I: Estimasi Laba
  sheet.setColumnWidth(10, 130); // J: Sisa Piutang

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:J1').merge()
    .setValue('📅  REKAPITULASI KEUANGAN BULANAN  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:J2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Bulan', 'Kas Masuk', 'Total Kas Keluar',
    '↳ HPP Produksi', '↳ Ops. Konveksi', '↳ Ops. Pribadi',
    'Arus Kas Bersih', 'Total Nilai Order', 'Estimasi Laba', 'Sisa Piutang'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  // Sub-headers highlight for Kas Keluar breakdown
  sheet.getRange('D3:F3').setBackground('#1F3864');
  sheet.getRange('C3').setBackground('#B22222');
  sheet.getRange('B3').setBackground('#1A6B36');

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
      "=IFERROR(SUMIFS('Rincian Biaya HPP'!$H$4:$H,'Rincian Biaya HPP'!$A$4:$A,\">=\"&" + from + ",'Rincian Biaya HPP'!$A$4:$A,\"<\"&" + to + "),0)",
      "=IFERROR(SUMIFS('Beban Konveksi'!$E$4:$E,'Beban Konveksi'!$A$4:$A,\">=\"&" + from + ",'Beban Konveksi'!$A$4:$A,\"<\"&" + to + "),0)",
      "=IFERROR(SUMIFS('Beban Pribadi'!$E$4:$E,'Beban Pribadi'!$A$4:$A,\">=\"&" + from + ",'Beban Pribadi'!$A$4:$A,\"<\"&" + to + "),0)",
      '=B' + r + '-C' + r,
      "=SUMIFS('Data Order'!$G$4:$G,'Data Order'!$A$4:$A,\">=\"&" + from + ",'Data Order'!$A$4:$A,\"<\"&" + to + ")",
      "=SUMIFS('Data Order'!$K$4:$K,'Data Order'!$A$4:$A,\">=\"&" + from + ",'Data Order'!$A$4:$A,\"<\"&" + to + ")",
      "=SUMIFS('Data Order'!$L$4:$L,'Data Order'!$A$4:$A,\">=\"&" + from + ",'Data Order'!$A$4:$A,\"<\"&" + to + ")"
    ]);
  }

  // setValues, bukan setFormulas: kolom pertama kini teks biasa.
  sheet.getRange(4, 1, rows.length, hdrs.length).setValues(rows);
  sheet.getRange('A4:A15').setHorizontalAlignment('left');
  sheet.getRange('B4:J15').setNumberFormat('"Rp"#,##0');

  // Alternating row colors
  for (var ri = 4; ri <= 15; ri++) {
    var bg = (ri % 2 === 0) ? '#EBF3FB' : CLR.rowOdd;
    sheet.setRowHeight(ri, ROW_H);
    sheet.getRange(ri, 1, 1, 10).setBackground(bg);
    sheet.getRange(ri, 1).setFontWeight('bold');
    sheet.getRange(ri, 7).setFontWeight('bold'); // Arus Kas Bersih
  }

  // Arus kas bersih diwarnai otomatis: merah saat minus
  sheet.getRange('G4:G16').setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');

  // ROW 16: TOTAL
  sheet.setRowHeight(16, 28);
  sheet.getRange(16, 1).setValue('TOTAL TAHUN ' + yr)
    .setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center');
  for (var col = 2; col <= 10; col++) {
    var cl = String.fromCharCode(64 + col);
    sheet.getRange(16, col)
      .setFormula('=SUM(' + cl + '4:' + cl + '15)')
      .setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.navy)
      .setNumberFormat('"Rp"#,##0').setHorizontalAlignment('right');
  }

  // Separator line above total
  sheet.getRange('A15:J15').setBorder(null, null, true, null, null, null,
    CLR.navy, SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
}

/* ========================================================================== */
/* TAB: ASET & VALUASI KONVEKSI                                                */
/* ========================================================================== */
function setupAsetSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 95);   // A: Tanggal Beli
  sheet.setColumnWidth(2, 110);  // B: Kode Aset
  sheet.setColumnWidth(3, 270);  // C: Nama Aset & Spesifikasi
  sheet.setColumnWidth(4, 210);  // D: Kategori Aset
  sheet.setColumnWidth(5, 65);   // E: Qty
  sheet.setColumnWidth(6, 75);   // F: Satuan
  sheet.setColumnWidth(7, 135);  // G: Harga Beli Satuan
  sheet.setColumnWidth(8, 135);  // H: Estimasi Nilai Satuan
  sheet.setColumnWidth(9, 145);  // I: Total Valuasi Pasar
  sheet.setColumnWidth(10, 115); // J: Kondisi
  sheet.setColumnWidth(11, 150); // K: Lokasi Penempatan
  sheet.setColumnWidth(12, 200); // L: Catatan / Keterangan

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:L1').merge()
    .setValue('🏷️  INVENTARIS & VALUASI ASET KONVEKSI  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#6C1005')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative Accent
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:L2').setBackground('#F59E0B');

  // ROW 3: Headers
  sheet.setRowHeight(3, 28);
  var hdrs = [
    'Tanggal Beli', 'Kode Aset', 'Nama Aset & Spesifikasi', 'Kategori',
    'Qty', 'Satuan', 'Harga Beli (Satuan)', 'Estimasi Nilai (Satuan)',
    'Total Valuasi Pasar', 'Kondisi', 'Lokasi Penempatan', 'Catatan / Keterangan'
  ];
  sheet.getRange(3, 1, 1, hdrs.length).setValues([hdrs])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#8A1407')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 13);

  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 2, rows, 1).setFontWeight('bold').setFontColor(CLR.navy).setHorizontalAlignment('center');
  sheet.getRange(4, 5, rows, 1).setHorizontalAlignment('center');
  sheet.getRange(4, 6, rows, 1).setHorizontalAlignment('center');
  sheet.getRange(4, 7, rows, 3).setNumberFormat('"Rp"#,##0');
  sheet.getRange(4, 10, rows, 1).setHorizontalAlignment('center');

  applyRowStripes(sheet, 13);
}

/* ========================================================================== */
/* TAB: LAPORAN LABA RUGI OPERASIONAL                                          */
/* ========================================================================== */
function setupLabaRugiSheet(sheet) {
  sheet.clear();

  // Column widths
  sheet.setColumnWidth(1, 320); // A: Komponen Laporan Keuangan
  sheet.setColumnWidth(2, 180); // B: Rincian / Subtotal
  sheet.setColumnWidth(3, 190); // C: Akumulasi Laba / Rugi
  sheet.setColumnWidth(4, 110); // D: Rasio Margin
  sheet.setColumnWidth(5, 290); // E: Sumber Data Transaksi

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:E1').merge()
    .setValue('📈  LAPORAN LABA RUGI OPERASIONAL  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#1F3864')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:E2').setBackground('#10B981');

  // ROW 3: Headers
  sheet.setRowHeight(3, 26);
  sheet.getRange(3, 1, 1, 5).setValues([['Komponen Laporan Keuangan', 'Rincian / Subtotal', 'Jumlah Total', 'Rasio %', 'Sumber Data Sheet']])
    .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white).setBackground('#2E5FAC')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // Row 4: SECTION 1 - PENDAPATAN
  sheet.setRowHeight(4, 26);
  sheet.getRange('A4:E4').setBackground('#E8F6ED');
  sheet.getRange('A4').setValue('1. PENDAPATAN OPERASIONAL (PENJUALAN ORDER)')
    .setFontSize(10).setFontWeight('bold').setFontColor('#1A6B36').setVerticalAlignment('middle');

  // Row 5: Total Penjualan Order
  sheet.setRowHeight(5, ROW_H);
  sheet.getRange('A5').setValue('  • Nilai Total Pesanan / Order (Gross Revenue)').setFontSize(9).setVerticalAlignment('middle');
  sheet.getRange('B5').setFormula('=IFERROR(SUM(\'Data Order\'!$G$4:$G),0)').setFontSize(9).setNumberFormat('"Rp"#,##0');
  sheet.getRange('E5').setValue('Tab "Data Order" (Kolom Total Nilai)').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');

  // Row 6: Realisasi Kas Masuk dari Order
  sheet.setRowHeight(6, ROW_H);
  sheet.getRange('A6').setValue('    - Pembayaran Masuk (Kas Tunai & DP/Pelunasan)').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');
  sheet.getRange('B6').setFormula('=IFERROR(SUM(\'Data Order\'!$H$4:$H)+SUM(\'Data Order\'!$I$4:$I),0)').setFontSize(8).setFontColor(CLR.gray2).setNumberFormat('"Rp"#,##0');
  sheet.getRange('E6').setValue('Tab "Data Order" (Kolom DP & Pelunasan)').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');

  // Row 7: Piutang Order Aktif
  sheet.setRowHeight(7, ROW_H);
  sheet.getRange('A7').setValue('    - Piutang Usaha (Tagihan Order Belum Lunas)').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');
  sheet.getRange('B7').setFormula('=IFERROR(SUM(\'Data Order\'!$L$4:$L),0)').setFontSize(8).setFontColor(CLR.gray2).setNumberFormat('"Rp"#,##0');
  sheet.getRange('E7').setValue('Tab "Data Order" (Kolom Sisa Tagihan)').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');

  // Row 8: Total Pendapatan Penjualan
  sheet.setRowHeight(8, 28);
  sheet.getRange(8, 1, 1, 5).setBackground('#F0FDF4');
  sheet.getRange('A8').setValue('TOTAL PENDAPATAN PENJUALAN').setFontSize(9).setFontWeight('bold').setFontColor('#166534').setVerticalAlignment('middle');
  sheet.getRange('C8').setFormula('=B5').setFontSize(10).setFontWeight('bold').setFontColor('#166534').setNumberFormat('"Rp"#,##0');
  sheet.getRange('D8').setValue('100.0%').setFontSize(9).setFontWeight('bold').setFontColor('#166534').setHorizontalAlignment('center');

  // Row 9: Spacing
  sheet.setRowHeight(9, 12);

  // Row 10: SECTION 2 - HARGA POKOK PENJUALAN
  sheet.setRowHeight(10, 26);
  sheet.getRange('A10:E10').setBackground('#FEE2E2');
  sheet.getRange('A10').setValue('2. HARGA POKOK PENJUALAN (HPP PRODUKSI)')
    .setFontSize(10).setFontWeight('bold').setFontColor('#991B1B').setVerticalAlignment('middle');

  // Row 11: Biaya Produksi (Kain, Sablon, Bordir, CMT)
  sheet.setRowHeight(11, ROW_H);
  sheet.getRange('A11').setValue('  • Bahan Baku Kain, Benang, Bordir, Sablon & CMT').setFontSize(9).setVerticalAlignment('middle');
  sheet.getRange('B11').setFormula('=IFERROR(SUM(\'Rincian Biaya HPP\'!$H$4:$H),0)').setFontSize(9).setNumberFormat('"Rp"#,##0');
  sheet.getRange('E11').setValue('Tab "Rincian Biaya HPP"').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');

  // Row 12: Total HPP
  sheet.setRowHeight(12, 28);
  sheet.getRange(12, 1, 1, 5).setBackground('#FEF2F2');
  sheet.getRange('A12').setValue('TOTAL HARGA POKOK PENJUALAN (HPP)').setFontSize(9).setFontWeight('bold').setFontColor('#991B1B').setVerticalAlignment('middle');
  sheet.getRange('C12').setFormula('=B11').setFontSize(10).setFontWeight('bold').setFontColor('#991B1B').setNumberFormat('"Rp"#,##0');
  sheet.getRange('D12').setFormula('=IF(C8>0, C12/C8, 0)').setFontSize(9).setNumberFormat('0.0%').setHorizontalAlignment('center');

  // Row 13: Spacing
  sheet.setRowHeight(13, 12);

  // Row 14: LABA KOTOR (GROSS PROFIT)
  sheet.setRowHeight(14, 32);
  sheet.getRange(14, 1, 1, 5).setBackground('#D1FAE5');
  sheet.getRange('A14').setValue('LABA KOTOR (GROSS PROFIT)').setFontSize(10).setFontWeight('bold').setFontColor('#065F46').setVerticalAlignment('middle');
  sheet.getRange('C14').setFormula('=C8-C12').setFontSize(11).setFontWeight('bold').setFontColor('#065F46').setNumberFormat('"Rp"#,##0');
  sheet.getRange('D14').setFormula('=IF(C8>0, C14/C8, 0)').setFontSize(10).setFontWeight('bold').setFontColor('#065F46').setNumberFormat('0.0%').setHorizontalAlignment('center');
  sheet.getRange('E14').setValue('Gross Profit Margin = Laba Kotor / Omzet').setFontSize(8).setFontColor('#065F46').setVerticalAlignment('middle');

  // Row 15: Spacing
  sheet.setRowHeight(15, 12);

  // Row 16: SECTION 3 - BEBAN OPERASIONAL KONVEKSI
  sheet.setRowHeight(16, 26);
  sheet.getRange('A16:E16').setBackground('#F1F5F9');
  sheet.getRange('A16').setValue('3. BEBAN OPERASIONAL KONVEKSI (OPEX)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.navy).setVerticalAlignment('middle');

  // Row 17: Pengeluaran Konveksi & Kantor
  sheet.setRowHeight(17, ROW_H);
  sheet.getRange('A17').setValue('  • Listrik Workshop, Maintenance Mesin, Sparepart, Packing').setFontSize(9).setVerticalAlignment('middle');
  sheet.getRange('B17').setFormula('=IFERROR(SUM(\'Beban Konveksi\'!$E$4:$E),0)').setFontSize(9).setNumberFormat('"Rp"#,##0');
  sheet.getRange('E17').setValue('Tab "Beban Konveksi"').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');

  // Row 18: Total Beban Konveksi
  sheet.setRowHeight(18, 28);
  sheet.getRange(18, 1, 1, 5).setBackground('#F8FAFC');
  sheet.getRange('A18').setValue('TOTAL BEBAN OPERASIONAL KONVEKSI').setFontSize(9).setFontWeight('bold').setFontColor(CLR.navy).setVerticalAlignment('middle');
  sheet.getRange('C18').setFormula('=B17').setFontSize(10).setFontWeight('bold').setFontColor(CLR.navy).setNumberFormat('"Rp"#,##0');
  sheet.getRange('D18').setFormula('=IF(C8>0, C18/C8, 0)').setFontSize(9).setNumberFormat('0.0%').setHorizontalAlignment('center');

  // Row 19: Spacing
  sheet.setRowHeight(19, 12);

  // Row 20: LABA OPERASIONAL KONVEKSI (EBIT)
  sheet.setRowHeight(20, 32);
  sheet.getRange(20, 1, 1, 5).setBackground('#DBEAFE');
  sheet.getRange('A20').setValue('LABA OPERASIONAL KONVEKSI (EBIT)').setFontSize(10).setFontWeight('bold').setFontColor('#1E40AF').setVerticalAlignment('middle');
  sheet.getRange('C20').setFormula('=C14-C18').setFontSize(11).setFontWeight('bold').setFontColor('#1E40AF').setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  sheet.getRange('D20').setFormula('=IF(C8>0, C20/C8, 0)').setFontSize(10).setFontWeight('bold').setFontColor('#1E40AF').setNumberFormat('0.0%').setHorizontalAlignment('center');
  sheet.getRange('E20').setValue('Operating Profit Konveksi Murni').setFontSize(8).setFontColor('#1E40AF').setVerticalAlignment('middle');

  // Row 21: Spacing
  sheet.setRowHeight(21, 12);

  // Row 22: SECTION 4 - PRIVE PEMILIK
  sheet.setRowHeight(22, 26);
  sheet.getRange('A22:E22').setBackground('#FDF2F8');
  sheet.getRange('A22').setValue('4. PENGELUARAN PRIBADI / PRIVE PEMILIK')
    .setFontSize(10).setFontWeight('bold').setFontColor('#9D174D').setVerticalAlignment('middle');

  // Row 23: Beban Pribadi
  sheet.setRowHeight(23, ROW_H);
  sheet.getRange('A23').setValue('  • Penarikan Dana Pribadi Pemilik (Prive)').setFontSize(9).setVerticalAlignment('middle');
  sheet.getRange('B23').setFormula('=IFERROR(SUM(\'Beban Pribadi\'!$E$4:$E),0)').setFontSize(9).setNumberFormat('"Rp"#,##0');
  sheet.getRange('E23').setValue('Tab "Beban Pribadi"').setFontSize(8).setFontColor(CLR.gray2).setVerticalAlignment('middle');

  // Row 24: Total Prive
  sheet.setRowHeight(24, 28);
  sheet.getRange(24, 1, 1, 5).setBackground('#FDF2F8');
  sheet.getRange('A24').setValue('TOTAL PRIVE PEMILIK').setFontSize(9).setFontWeight('bold').setFontColor('#9D174D').setVerticalAlignment('middle');
  sheet.getRange('C24').setFormula('=B23').setFontSize(10).setFontWeight('bold').setFontColor('#9D174D').setNumberFormat('"Rp"#,##0');
  sheet.getRange('D24').setFormula('=IF(C8>0, C24/C8, 0)').setFontSize(9).setNumberFormat('0.0%').setHorizontalAlignment('center');

  // Row 25: Spacing
  sheet.setRowHeight(25, 14);

  // Row 26: LABA BERSIH AKHIR (LABA DITAHAN)
  sheet.setRowHeight(26, 36);
  sheet.getRange(26, 1, 1, 5).setBackground('#6C1005');
  sheet.getRange('A26').setValue('LABA BERSIH AKHIR USAHA (LABA DITAHAN)').setFontSize(11).setFontWeight('bold').setFontColor(CLR.white).setVerticalAlignment('middle');
  sheet.getRange('C26').setFormula('=C20-C24').setFontSize(12).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  sheet.getRange('D26').setFormula('=IF(C8>0, C26/C8, 0)').setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('0.0%').setHorizontalAlignment('center');
  sheet.getRange('E26').setValue('Akumulasi modal masuk ke Neraca Pasiva').setFontSize(8).setFontColor('#FDE8E8').setVerticalAlignment('middle');
}

/* ========================================================================== */
/* TAB: NERACA POSISI KEUANGAN (BENTUK SKONTRO / KIRI & KANAN)                 */
/* ========================================================================== */
function setupNeracaSheet(sheet) {
  sheet.clear();

  // Column widths: Kolom A-D (Aktiva/Kiri), Kolom E (Separator), Kolom F-I (Pasiva/Kanan)
  sheet.setColumnWidth(1, 35);   // A: No Aktiva
  sheet.setColumnWidth(2, 230);  // B: Keterangan Aktiva
  sheet.setColumnWidth(3, 115);  // C: Rincian Aktiva (Rp)
  sheet.setColumnWidth(4, 140);  // D: Subtotal Aktiva (Rp)
  sheet.setColumnWidth(5, 25);   // E: Divider Spasi
  sheet.setColumnWidth(6, 35);   // F: No Pasiva
  sheet.setColumnWidth(7, 230);  // G: Keterangan Pasiva
  sheet.setColumnWidth(8, 115);  // H: Rincian Pasiva (Rp)
  sheet.setColumnWidth(9, 140);  // I: Subtotal Pasiva (Rp)

  // ROW 1: Banner
  sheet.setRowHeight(1, 45);
  sheet.getRange('A1:I1').merge()
    .setValue('⚖️  NERACA KEUANGAN & VALUASI USAHA (BENTUK SKONTRO)  ·  ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(13).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground('#1F3864')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 2: Decorative Accent
  sheet.setRowHeight(2, 4);
  sheet.getRange('A2:D2').setBackground('#10B981'); // Hijau Aktiva
  sheet.getRange('E2').setBackground('#CBD5E1');
  sheet.getRange('F2:I2').setBackground('#6C1005'); // Marun Pasiva

  // ROW 3: Column Headers (Skontro: Kiri Aktiva, Kanan Pasiva)
  sheet.setRowHeight(3, 28);
  sheet.getRange('A3:D3').merge().setValue('SISI KIRI: AKTIVA (ASET)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground('#166534')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.getRange('E3').setBackground('#F1F5F9');
  sheet.getRange('F3:I3').merge().setValue('SISI KANAN: PASIVA (KEWAJIBAN & EKUITAS)')
    .setFontSize(10).setFontWeight('bold').setFontColor(CLR.white).setBackground('#8A1407')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');

  // ROW 4: Section 1 Subheaders
  sheet.setRowHeight(4, 24);
  sheet.getRange('A4:D4').setBackground('#E8F6ED');
  sheet.getRange('A4:B4').merge().setValue('I. ASET LANCAR')
    .setFontSize(9).setFontWeight('bold').setFontColor('#166534').setVerticalAlignment('middle');

  sheet.getRange('E4').setBackground('#F8FAFC');

  sheet.getRange('F4:I4').setBackground('#FEF3C7');
  sheet.getRange('F4:G4').merge().setValue('I. KEWAJIBAN / UTANG (LIABILITAS)')
    .setFontSize(9).setFontWeight('bold').setFontColor('#92400E').setVerticalAlignment('middle');

  // ROW 5: Kas & Utang Pinjaman
  sheet.setRowHeight(5, ROW_H);
  sheet.getRange('A5').setValue('1').setFontSize(8).setHorizontalAlignment('center');
  sheet.getRange('B5').setValue('Kas & Saldo Bank (Arus Kas)').setFontSize(8.5).setVerticalAlignment('middle');
  sheet.getRange('C5').setFormula('=IFERROR(SUM(\'Arus Kas\'!$F$4:$F)-SUM(\'Arus Kas\'!$G$4:$G),0)').setFontSize(8.5).setNumberFormat('"Rp"#,##0');

  sheet.getRange('E5').setBackground('#F8FAFC');

  sheet.getRange('F5').setValue('1').setFontSize(8).setHorizontalAlignment('center');
  sheet.getRange('G5').setValue('Utang Pinjaman & Dana Talangan').setFontSize(8.5).setVerticalAlignment('middle');
  sheet.getRange('H5').setFormula('=IFERROR(SUM(Pinjaman!$F$4:$F),0)').setFontSize(8.5).setNumberFormat('"Rp"#,##0');

  // ROW 6: Piutang & Utang Vendor
  sheet.setRowHeight(6, ROW_H);
  sheet.getRange('A6').setValue('2').setFontSize(8).setHorizontalAlignment('center');
  sheet.getRange('B6').setValue('Piutang Usaha (Tagihan Customer)').setFontSize(8.5).setVerticalAlignment('middle');
  sheet.getRange('C6').setFormula('=IFERROR(SUM(\'Data Order\'!$L$4:$L),0)').setFontSize(8.5).setNumberFormat('"Rp"#,##0');

  sheet.getRange('E6').setBackground('#F8FAFC');

  sheet.getRange('F6').setValue('2').setFontSize(8).setHorizontalAlignment('center');
  sheet.getRange('G6').setValue('Utang Usaha / Vendor Bahan (Tempo)').setFontSize(8.5).setVerticalAlignment('middle');
  sheet.getRange('H6').setValue(0).setFontSize(8.5).setNumberFormat('"Rp"#,##0');

  // ROW 7: Subtotal Aset Lancar & Subtotal Kewajiban
  sheet.setRowHeight(7, 26);
  sheet.getRange('A7:D7').setBackground('#F0FDF4');
  sheet.getRange('A7:B7').merge().setValue('Total Aset Lancar').setFontSize(9).setFontWeight('bold').setFontColor('#166534');
  sheet.getRange('D7').setFormula('=SUM(C5:C6)').setFontSize(9).setFontWeight('bold').setFontColor('#166534').setNumberFormat('"Rp"#,##0');

  sheet.getRange('E7').setBackground('#F8FAFC');

  sheet.getRange('F7:I7').setBackground('#FFFBEB');
  sheet.getRange('F7:G7').merge().setValue('Total Kewajiban (Utang)').setFontSize(9).setFontWeight('bold').setFontColor('#92400E');
  sheet.getRange('I7').setFormula('=SUM(H5:H6)').setFontSize(9).setFontWeight('bold').setFontColor('#92400E').setNumberFormat('"Rp"#,##0');

  // ROW 8: Spacing
  sheet.setRowHeight(8, 10);
  sheet.getRange('A8:I8').setBackground('#FFFFFF');

  // ROW 9: Section 2 Subheaders (Aset Tetap & Ekuitas)
  sheet.setRowHeight(9, 24);
  sheet.getRange('A9:D9').setBackground('#E8F6ED');
  sheet.getRange('A9:B9').merge().setValue('II. ASET TETAP KONVEKSI (VALUASI PASAR)')
    .setFontSize(9).setFontWeight('bold').setFontColor('#166534').setVerticalAlignment('middle');

  sheet.getRange('E9').setBackground('#F8FAFC');

  sheet.getRange('F9:I9').setBackground('#FCE7F3');
  sheet.getRange('F9:G9').merge().setValue('II. EKUITAS (MODAL BERSIH)')
    .setFontSize(9).setFontWeight('bold').setFontColor('#9D174D').setVerticalAlignment('middle');

  // Fixed Asset rows (10 to 16)
  var fixedAssetItems = [
    { no: '1', name: 'Mesin Jahit & Obras', pattern: '*Mesin Jahit*' },
    { no: '2', name: 'Mesin Potong & Meja Pola', pattern: '*Potong*' },
    { no: '3', name: 'Sablon, Heatpress & Bordir', pattern: '*Sablon*' },
    { no: '4', name: 'Setrika Uap Boiler & Finishing', pattern: '*Setrika*' },
    { no: '5', name: 'Komputer Desain & Elektronik', pattern: '*Komputer*' },
    { no: '6', name: 'Fasilitas Workshop & Kendaraan', pattern: '*Fasilitas*' },
    { no: '7', name: 'Aset & Peralatan Lainnya', pattern: '*Lainnya*' }
  ];

  for (var fa = 0; fa < fixedAssetItems.length; fa++) {
    var rowIdx = 10 + fa;
    sheet.setRowHeight(rowIdx, ROW_H);

    // Kiri: Aset Tetap
    sheet.getRange('A' + rowIdx).setValue(fixedAssetItems[fa].no).setFontSize(8).setHorizontalAlignment('center');
    sheet.getRange('B' + rowIdx).setValue(fixedAssetItems[fa].name).setFontSize(8.5).setVerticalAlignment('middle');
    sheet.getRange('C' + rowIdx).setFormula('=IFERROR(SUMIFS(Aset!$I$4:$I, Aset!$D$4:$D, "' + fixedAssetItems[fa].pattern + '"),0)')
      .setFontSize(8.5).setNumberFormat('"Rp"#,##0');

    sheet.getRange('E' + rowIdx).setBackground('#F8FAFC');

    // Kanan: Ekuitas baris 10 & 11, baris 12-16 kosong/penjelasan
    if (fa === 0) {
      sheet.getRange('F10').setValue('1').setFontSize(8).setHorizontalAlignment('center');
      sheet.getRange('G10').setValue('Modal Disetor Pemilik (Valuasi Awal)').setFontSize(8.5).setVerticalAlignment('middle');
      sheet.getRange('H10').setFormula('=MAX(0, D19-I7-H11)').setFontSize(8.5).setFontWeight('bold').setNumberFormat('"Rp"#,##0');
    } else if (fa === 1) {
      sheet.getRange('F11').setValue('2').setFontSize(8).setHorizontalAlignment('center');
      sheet.getRange('G11').setValue('Akumulasi Laba Ditahan Usaha').setFontSize(8.5).setVerticalAlignment('middle');
      sheet.getRange('H11').setFormula('=\'Laba Rugi\'!$C$26').setFontSize(8.5).setFontWeight('bold').setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
    } else if (fa === 2) {
      sheet.getRange('F12:I12').merge().setValue('Ket: Modal Pemilik dihitung dari Total Aset dikurangi Kewajiban & Laba Ditahan.')
        .setFontSize(7.5).setFontColor(CLR.gray2).setVerticalAlignment('middle');
    }
  }

  // ROW 17: Subtotal Aset Tetap & Subtotal Ekuitas
  sheet.setRowHeight(17, 26);
  sheet.getRange('A17:D17').setBackground('#F0FDF4');
  sheet.getRange('A17:B17').merge().setValue('Total Aset Tetap Konveksi').setFontSize(9).setFontWeight('bold').setFontColor('#166534');
  sheet.getRange('D17').setFormula('=SUM(C10:C16)').setFontSize(9).setFontWeight('bold').setFontColor('#166534').setNumberFormat('"Rp"#,##0');

  sheet.getRange('E17').setBackground('#F8FAFC');

  sheet.getRange('F17:I17').setBackground('#FDF2F8');
  sheet.getRange('F17:G17').merge().setValue('Total Ekuitas (Modal Bersih)').setFontSize(9).setFontWeight('bold').setFontColor('#9D174D');
  sheet.getRange('I17').setFormula('=SUM(H10:H11)').setFontSize(9).setFontWeight('bold').setFontColor('#9D174D').setNumberFormat('"Rp"#,##0');

  // ROW 18: Spacing
  sheet.setRowHeight(18, 10);
  sheet.getRange('A18:I18').setBackground('#FFFFFF');

  // ROW 19: TOTAL AKTIVA (KIRI) vs TOTAL PASIVA (KANAN)
  sheet.setRowHeight(19, 36);
  sheet.getRange('A19:D19').setBackground('#166534');
  sheet.getRange('A19:B19').merge().setValue('TOTAL AKTIVA (ASET)')
    .setFontSize(10.5).setFontWeight('bold').setFontColor(CLR.white).setVerticalAlignment('middle');
  sheet.getRange('D19').setFormula('=D7+D17')
    .setFontSize(11.5).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('"Rp"#,##0').setVerticalAlignment('middle');

  sheet.getRange('E19').setBackground('#CBD5E1');

  sheet.getRange('F19:I19').setBackground('#1F3864');
  sheet.getRange('F19:G19').merge().setValue('TOTAL PASIVA (KEWAJIBAN & EKUITAS)')
    .setFontSize(10.5).setFontWeight('bold').setFontColor(CLR.white).setVerticalAlignment('middle');
  sheet.getRange('I19').setFormula('=I7+I17')
    .setFontSize(11.5).setFontWeight('bold').setFontColor(CLR.white).setNumberFormat('"Rp"#,##0').setVerticalAlignment('middle');

  // ROW 20: Spacing
  sheet.setRowHeight(20, 10);
  sheet.getRange('A20:I20').setBackground('#FFFFFF');

  // ROW 21: STATUS BANNER KESEIMBANGAN & VALUASI NET WORTH
  sheet.setRowHeight(21, 34);
  sheet.getRange('A21:D21').merge()
    .setFormula('=IF(D19=I19, "✅ NERACA SEIMBANG (AKTIVA = PASIVA)", "⚠️ SELISIH: " & TEXT(D19-I19, "Rp#,##0"))')
    .setFontSize(10).setFontWeight('bold').setFontColor('#065F46').setBackground('#D1FAE5')
    .setHorizontalAlignment('center').setVerticalAlignment('middle')
    .setBorder(true, true, true, true, false, false, '#10B981', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  sheet.getRange('E21').setBackground('#F8FAFC');

  sheet.getRange('F21:I21').merge()
    .setFormula('="💎 VALUASI BERSIH (NET WORTH): " & TEXT(D19-I7, "Rp#,##0")')
    .setFontSize(10).setFontWeight('bold').setFontColor('#831843').setBackground('#FCE7F3')
    .setHorizontalAlignment('center').setVerticalAlignment('middle')
    .setBorder(true, true, true, true, false, false, '#EC4899', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
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
          o.total_cost, o.estimated_profit, o.remaining,
          (o.cash_advance !== undefined ? o.cash_advance : (o.cash_deficit || 0)),
          o.status, o.notes,
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

    if (data.expenses && data.expenses.length > 0) {
      data.expenses.forEach(function(exp) {
        entries.push({
          id:      exp.id || '',
          date:    exp.date,
          trx_no:  exp.trx_no || exp.reference_no,
          desc:    exp.desc || exp.title || 'Beban Operasional',
          cat:     exp.category || 'Beban Operasional',
          party:   exp.party || 'Vendor / Operasional',
          inflow:  0,
          outflow: exp.amount || 0,
          method:  exp.method || 'Transfer / Kas'
        });
      });
    }

    if (data.loans && data.loans.length > 0) {
      data.loans.forEach(function(l) {
        entries.push({
          id:      l.id || l.reference_no,
          date:    l.date,
          trx_no:  l.reference_no,
          desc:    'Penerimaan Pinjaman dari ' + l.lender_name + (l.purpose && l.purpose !== '-' ? ' (' + l.purpose + ')' : ''),
          cat:     'Pinjaman (Pihak Luar)',
          party:   l.lender_name,
          inflow:  l.amount || 0,
          outflow: 0,
          method:  'Transfer / Kas'
        });
      });
    }

    if (data.loan_payments && data.loan_payments.length > 0) {
      data.loan_payments.forEach(function(lp) {
        entries.push({
          id:      lp.id || lp.reference_no,
          date:    lp.date,
          trx_no:  lp.reference_no,
          desc:    'Bayar/Cicil Pinjaman ke ' + (lp.lender_name || 'Kreditur'),
          cat:     'Pembayaran Utang',
          party:   lp.lender_name || 'Kreditur',
          inflow:  0,
          outflow: lp.amount || 0,
          method:  lp.method || 'Transfer Bank'
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

  // 4. Beban Konveksi & Kantor (Overhead)
  var convectionSheet = ss.getSheetByName('Beban Konveksi');
  if (convectionSheet && data.expenses) {
    clearSheetDataRows(convectionSheet, 4);
    var convExpenses = data.expenses.filter(function(e) {
      return e.expense_type === 'convection' || (!e.expense_type && (e.desc || '').indexOf('Konveksi') !== -1);
    });
    if (convExpenses.length > 0) {
      var convRows = convExpenses.map(function(e) {
        return [
          e.date,
          e.trx_no || e.reference_no,
          e.category_name || e.party || e.category,
          e.title || e.desc,
          e.amount || 0,
          e.method || 'Transfer / Kas',
          e.recorded_by || 'Admin',
          e.notes || '-',
          e.id || ''
        ];
      });
      convectionSheet.getRange(4, 1, convRows.length, convRows[0].length).setValues(convRows);
    }
  }

  // 5. Beban Pribadi & Prive Owner
  var personalSheet = ss.getSheetByName('Beban Pribadi');
  if (personalSheet && data.expenses) {
    clearSheetDataRows(personalSheet, 4);
    var persExpenses = data.expenses.filter(function(e) {
      return e.expense_type === 'personal' || (!e.expense_type && (e.desc || '').indexOf('Pribadi') !== -1);
    });
    if (persExpenses.length > 0) {
      var persRows = persExpenses.map(function(e) {
        return [
          e.date,
          e.trx_no || e.reference_no,
          e.category_name || e.party || e.category,
          e.title || e.desc,
          e.amount || 0,
          e.method || 'Transfer / Kas',
          e.recorded_by || 'Admin',
          e.notes || '-',
          e.id || ''
        ];
      });
      personalSheet.getRange(4, 1, persRows.length, persRows[0].length).setValues(persRows);
    }
  }

  // 6. Pinjaman Pihak Luar
  var pinjamanSheet = ss.getSheetByName('Pinjaman');
  if (pinjamanSheet && data.loans) {
    clearSheetDataRows(pinjamanSheet, 4);
    if (data.loans.length > 0) {
      var loanRows = data.loans.map(function(l) {
        return [
          l.date,
          l.reference_no,
          l.lender_name,
          l.amount || 0,
          l.amount_paid || 0,
          l.remaining_amount || 0,
          l.due_date || '-',
          l.status || 'Belum Lunas',
          l.order_number || '-',
          l.purpose && l.purpose !== '-' ? l.purpose : (l.notes || '-'),
          l.id || l.reference_no || ''
        ];
      });
      pinjamanSheet.getRange(4, 1, loanRows.length, loanRows[0].length).setValues(loanRows);
    }
  }

  // 7. Aset & Valuasi Konveksi
  var asetSheet = ss.getSheetByName('Aset');
  if (asetSheet && data.assets) {
    clearSheetDataRows(asetSheet, 4);
    if (data.assets.length > 0) {
      var assetRows = data.assets.map(function(a) {
        var qty = a.qty || 1;
        var curVal = a.current_value !== undefined ? a.current_value : (a.purchase_price || 0);
        return [
          a.date,
          a.code,
          a.name,
          a.category,
          qty,
          a.unit || 'unit',
          a.purchase_price || 0,
          curVal,
          qty * curVal,
          a.condition || 'Baik',
          a.location || '-',
          a.notes || '-',
          a.id || a.code || ''
        ];
      });
      asetSheet.getRange(4, 1, assetRows.length, assetRows[0].length).setValues(assetRows);
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
    orderData.estimated_profit, orderData.remaining,
    (orderData.cash_advance !== undefined ? orderData.cash_advance : (orderData.cash_deficit || 0)),
    orderData.status, orderData.notes,
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

function handleAddExpense(expData, ss) {
  var cashSheet = ss.getSheetByName('Arus Kas');
  var convSheet = ss.getSheetByName('Beban Konveksi');
  var persSheet = ss.getSheetByName('Beban Pribadi');

  var e = Array.isArray(expData) ? expData : [
    expData.date, expData.trx_no || expData.reference_no, expData.desc || expData.title,
    expData.category || 'Beban Operasional', expData.party || 'Vendor / Operasional',
    0, expData.amount || 0, '', expData.method || 'Transfer / Kas', expData.id || ''
  ];

  var expId = Array.isArray(expData) ? expData[9] : (expData.id || expData.reference_no || '');
  var expType = Array.isArray(expData) ? '' : (expData.expense_type || '');
  if (!expType) {
    var descStr = String(Array.isArray(expData) ? expData[2] : (expData.desc || expData.title || ''));
    expType = (descStr.indexOf('Pribadi') !== -1 || descStr.indexOf('Prive') !== -1) ? 'personal' : 'convection';
  }

  // 1. Simpan ke tab khusus sesuai jenis pengeluaran
  var targetSheet = (expType === 'personal') ? persSheet : convSheet;
  var otherSheet  = (expType === 'personal') ? convSheet : persSheet;

  if (targetSheet) {
    var specificRow = [
      Array.isArray(expData) ? expData[0] : expData.date,
      Array.isArray(expData) ? expData[1] : (expData.trx_no || expData.reference_no),
      Array.isArray(expData) ? expData[3] : (expData.category_name || expData.party || expData.category || 'Operasional'),
      Array.isArray(expData) ? expData[2] : (expData.title || expData.desc),
      Array.isArray(expData) ? expData[6] : (expData.amount || 0),
      Array.isArray(expData) ? expData[8] : (expData.method || 'Transfer / Kas'),
      Array.isArray(expData) ? 'Admin' : (expData.recorded_by || 'Admin'),
      Array.isArray(expData) ? '-' : (expData.notes || '-'),
      expId
    ];

    var exTarget = findRowById(targetSheet, 9, expId);
    if (exTarget > 0) {
      targetSheet.getRange(exTarget, 1, 1, specificRow.length).setValues([specificRow]);
    } else {
      targetSheet.appendRow(specificRow);
    }
  }

  // Jika diedit berpindah jenis, hapus dari sheet pasangannya
  if (otherSheet && expId) {
    deleteRowById(otherSheet, 9, expId);
  }

  // 2. Simpan ke tab master Arus Kas
  if (cashSheet) {
    var existing = findRowById(cashSheet, 10, e[9]);
    var nextRow = existing > 0 ? existing : Math.max(cashSheet.getLastRow() + 1, 4);
    var cashRow = [
      e[0], e[1], e[2],
      e[3], e[4],
      0, e[6],
      '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
      e[8], e[9]
    ];

    if (existing > 0) {
      cashSheet.getRange(existing, 1, 1, cashRow.length).setValues([cashRow]);
    } else {
      cashSheet.appendRow(cashRow);
    }
  }
}

function handleUpsertLoan(loanData, ss) {
  var sheet = ss.getSheetByName('Pinjaman');
  var cashSheet = ss.getSheetByName('Arus Kas');
  if (!sheet) return;

  var l = Array.isArray(loanData) ? loanData : [
    loanData.date, loanData.reference_no, loanData.lender_name,
    loanData.amount || 0, loanData.amount_paid || 0, loanData.remaining_amount || 0,
    loanData.due_date || '-', loanData.status || 'Belum Lunas',
    loanData.order_number || '-',
    loanData.purpose && loanData.purpose !== '-' ? loanData.purpose : (loanData.notes || '-'),
    loanData.id || loanData.reference_no || ''
  ];

  var existing = findRowById(sheet, 11, l[10]);
  if (existing > 0) {
    sheet.getRange(existing, 1, 1, l.length).setValues([l]);
  } else {
    sheet.appendRow(l);
  }

  // Rekam penerimaan pinjaman di Arus Kas sebagai Kas Masuk
  if (cashSheet) {
    var cashExisting = findRowById(cashSheet, 10, l[10]);
    var nextRow = cashExisting > 0 ? cashExisting : Math.max(cashSheet.getLastRow() + 1, 4);
    var cashRow = [
      l[0], l[1], 'Pinjaman dari ' + l[2] + (l[9] && l[9] !== '-' ? ' (' + l[9] + ')' : ''),
      'Pinjaman (Pihak Luar)', l[2],
      l[3], 0,
      '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
      'Transfer / Kas', l[10]
    ];

    if (cashExisting > 0) {
      cashSheet.getRange(cashExisting, 1, 1, cashRow.length).setValues([cashRow]);
    } else {
      cashSheet.appendRow(cashRow);
    }
  }
}

function handleAddLoanPayment(lpData, ss) {
  var cashSheet = ss.getSheetByName('Arus Kas');
  if (!cashSheet) return;

  var p = Array.isArray(lpData) ? lpData : [
    lpData.date, lpData.reference_no,
    'Bayar/Cicil Pinjaman ke ' + (lpData.lender_name || 'Kreditur'),
    'Pembayaran Utang', lpData.lender_name || 'Kreditur',
    0, lpData.amount || 0, '', lpData.method || 'Transfer Bank',
    lpData.id || lpData.reference_no || ''
  ];

  var existing = findRowById(cashSheet, 10, p[9]);
  var nextRow = existing > 0 ? existing : Math.max(cashSheet.getLastRow() + 1, 4);
  var cashRow = [
    p[0], p[1], p[2],
    p[3], p[4],
    0, p[6],
    '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
    p[8] || 'Transfer Bank', p[9]
  ];

  if (existing > 0) {
    cashSheet.getRange(existing, 1, 1, cashRow.length).setValues([cashRow]);
  } else {
    cashSheet.appendRow(cashRow);
  }
}

function handleUpsertAsset(assetData, ss) {
  var sheet = ss.getSheetByName('Aset');
  if (!sheet) return;

  var qty = assetData.qty || 1;
  var curVal = assetData.current_value !== undefined ? assetData.current_value : (assetData.purchase_price || 0);

  var a = Array.isArray(assetData) ? assetData : [
    assetData.date,
    assetData.code,
    assetData.name,
    assetData.category,
    qty,
    assetData.unit || 'unit',
    assetData.purchase_price || 0,
    curVal,
    qty * curVal,
    assetData.condition || 'Baik',
    assetData.location || '-',
    assetData.notes || '-',
    assetData.id || assetData.code || ''
  ];

  var existing = findRowById(sheet, 13, a[12]);
  if (existing > 0) {
    sheet.getRange(existing, 1, 1, a.length).setValues([a]);
  } else {
    sheet.appendRow(a);
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
    deleteRowById(orderSheet, 16, data.id);

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
  } else if (data.entity === 'expense') {
    deleteRowById(cashSheet, 10, data.id);
    var convSheet = ss.getSheetByName('Beban Konveksi');
    if (convSheet) deleteRowById(convSheet, 9, data.id);
    var persSheet = ss.getSheetByName('Beban Pribadi');
    if (persSheet) deleteRowById(persSheet, 9, data.id);
  } else if (data.entity === 'loan') {
    var pinjSheet = ss.getSheetByName('Pinjaman');
    if (pinjSheet) deleteRowById(pinjSheet, 11, data.id);
    deleteRowById(cashSheet, 10, data.id);
    var pIds = data.payment_ids || [];
    for (var k = 0; k < pIds.length; k++) {
      deleteRowById(cashSheet, 10, pIds[k]);
    }
  } else if (data.entity === 'loan_payment') {
    deleteRowById(cashSheet, 10, data.id);
  } else if (data.entity === 'asset') {
    var asetSheet = ss.getSheetByName('Aset');
    if (asetSheet) deleteRowById(asetSheet, 13, data.id);
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

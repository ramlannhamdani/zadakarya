/**
 * ============================================================================
 * ZADA KARYA PRODUCTION - GOOGLE SPREADSHEET AUTOMATION & WEBHOOK (ATM TEMPLATE)
 * ============================================================================
 * Struktur Terpadu (Di-ATM dari Template Keuangan & Arus Kas Zada Karya):
 * 1. Dashboard         : Kartu KPI Keuangan, Grafik Arus Kas Bulanan, Ringkasan HPP
 * 2. Arus Kas          : Buku Kas Harian (Kas Masuk & Kas Keluar terpadu + Saldo Berjalan)
 * 3. Data Order        : Database Pesanan, Qty, Nilai Order, DP, Pelunasan, HPP, Laba, Sisa Tagihan
 * 4. Rincian Biaya HPP : Drill-down rincian pengeluaran produksi per satuan x kuantitas
 * 5. Rekap Bulanan     : 12 Bulan performa kas masuk, kas keluar, omset, dan laba
 * ============================================================================
 */

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.tryLock(20000);

  try {
    if (!e || !e.postData || !e.postData.contents) {
      return respondJSON({ status: 'error', message: 'Payload data kosong.' });
    }

    var data = JSON.parse(e.postData.contents);

    // Setup action
    if (data.action === 'setup') {
      setupSheet();
      return respondJSON({ status: 'success', message: 'Setup sheet ATM berhasil dijalankan!' });
    }

    // Ping action
    if (data.action === 'ping') {
      return respondJSON({ status: 'success', message: 'Zada Karya Webhook Service Active' });
    }

    var ss = SpreadsheetApp.getActiveSpreadsheet();

    // Batch Sync All
    if (data.action === 'sync_all' || data.orders || Array.isArray(data)) {
      handleSyncAll(data, ss);
      return respondJSON({ status: 'success', message: 'Data synced successfully' });
    }

    // Single item handling
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
  return respondJSON({
    status: 'success',
    message: 'Zada Karya Webhook Service Active'
  });
}

function respondJSON(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

/**
 * SETUP SELURUH TAB & FORMULA SESUAI TEMPLATE ARUS KAS ZADA KARYA
 */
function setupSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  // Pastikan locale English US agar parsing formula SUMIFS, DATE, IFERROR standar & bebas error
  try {
    ss.setSpreadsheetLocale('en_US');
    ss.setSpreadsheetTimeZone('Asia/Jakarta');
  } catch(e) {}

  // 1. Tab Arus Kas (Buku Kas Umum)
  var cashSheet = getOrCreateSheet(ss, 'Arus Kas');
  setupArusKasSheet(cashSheet);

  // 2. Tab Data Order
  var orderSheet = getOrCreateSheet(ss, 'Data Order');
  setupDataOrderSheet(orderSheet);

  // 3. Tab Rincian Biaya HPP
  var costSheet = getOrCreateSheet(ss, 'Rincian Biaya HPP');
  setupRincianHppSheet(costSheet);

  // 4. Tab Rekap Bulanan
  var rekapSheet = getOrCreateSheet(ss, 'Rekap Bulanan');
  setupRekapBulananSheet(rekapSheet);

  // 5. Tab Dashboard
  var dashSheet = getOrCreateSheet(ss, 'Dashboard');
  setupDashboardSheet(dashSheet, rekapSheet);

  // Hapus tab default lama jika ada
  var oldTabs = ['Sheet1', 'Data_Pesanan', 'Data_Biaya_HPP', 'Data_Pembayaran', 'Dashboard Laporan'];
  for (var i = 0; i < oldTabs.length; i++) {
    var oldSheet = ss.getSheetByName(oldTabs[i]);
    if (oldSheet && ss.getSheets().length > 1) {
      try { ss.deleteSheet(oldSheet); } catch(e) {}
    }
  }

  // Posisikan Dashboard di paling depan
  ss.setActiveSheet(dashSheet);
  ss.moveActiveSheet(1);
}

/* -------------------------------------------------------------------------- */
/* TAB: ARUS KAS                                                              */
/* -------------------------------------------------------------------------- */
function setupArusKasSheet(sheet) {
  sheet.clear();
  sheet.getRange('A1:I1').merge()
    .setValue('ARUS KAS HARIAN — ZADA KARYA PRODUCTION')
    .setFontSize(12).setFontWeight('bold').setFontColor('#FFFFFF').setBackground('#1F497D')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(1, 35);

  var headers = [
    'Tanggal', 'No. Transaksi', 'Keterangan', 'Kategori', 'Customer/Supplier',
    'Kas Masuk', 'Kas Keluar', 'Saldo', 'Metode Pembayaran'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontWeight('bold').setFontSize(9).setBackground('#2F5597').setFontColor('#FFFFFF')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(3, 28);
  sheet.setFrozenRows(3);

  // Formats
  sheet.getRange('A4:A500').setNumberFormat('yyyy-mm-dd');
  sheet.getRange('F4:H500').setNumberFormat('"Rp"#,##0');

  // Pasang rumus saldo dinamis untuk baris 4 sampai 500
  var saldoFormulas = [];
  for (var r = 4; r <= 500; r++) {
    saldoFormulas.push(['=IF(A' + r + '="","",SUM($F$4:F' + r + ')-SUM($G$4:G' + r + '))']);
  }
  sheet.getRange('H4:H500').setFormulas(saldoFormulas);

  autoFitColumns(sheet, 9);
}

/* -------------------------------------------------------------------------- */
/* TAB: DATA ORDER                                                            */
/* -------------------------------------------------------------------------- */
function setupDataOrderSheet(sheet) {
  sheet.clear();
  sheet.getRange('A1:N1').merge()
    .setValue('DATA ORDER & PROFITABILITAS — ZADA KARYA PRODUCTION')
    .setFontSize(12).setFontWeight('bold').setFontColor('#FFFFFF').setBackground('#1F497D')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(1, 35);

  var headers = [
    'Tanggal', 'No. Order', 'Customer', 'Produk', 'Qty', 'Harga/pcs',
    'Nilai Order', 'DP', 'Pelunasan', 'Total HPP', 'Estimasi Laba',
    'Sisa Tagihan', 'Status', 'Catatan'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontWeight('bold').setFontSize(9).setBackground('#2F5597').setFontColor('#FFFFFF')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(3, 28);
  sheet.setFrozenRows(3);

  sheet.getRange('A4:A300').setNumberFormat('yyyy-mm-dd');
  sheet.getRange('E4:E300').setNumberFormat('#,##0');
  sheet.getRange('F4:L300').setNumberFormat('"Rp"#,##0');

  // Formats & formulas for Nilai Order, Estimasi Laba, Sisa Tagihan
  var formulasOrder = [];
  for (var r = 4; r <= 300; r++) {
    formulasOrder.push([
      '=IF(E' + r + '="","",IF(G' + r + '>0,G' + r + ',E' + r + '*F' + r + '))', // Nilai Order (Col G)
      '=IF(G' + r + '="","",G' + r + '-J' + r + ')',                             // Estimasi Laba (Col K)
      '=IF(G' + r + '="","",G' + r + '-H' + r + '-I' + r + ')'                   // Sisa Tagihan (Col L)
    ]);
  }

  autoFitColumns(sheet, 14);
}

/* -------------------------------------------------------------------------- */
/* TAB: RINCIAN BIAYA HPP                                                     */
/* -------------------------------------------------------------------------- */
function setupRincianHppSheet(sheet) {
  sheet.clear();
  sheet.getRange('A1:I1').merge()
    .setValue('RINCIAN BIAYA PRODUKSI (HPP) — ZADA KARYA PRODUCTION')
    .setFontSize(12).setFontWeight('bold').setFontColor('#FFFFFF').setBackground('#1F497D')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(1, 35);

  var headers = [
    'Tanggal', 'No. Order', 'Kategori', 'Rincian Biaya',
    'Qty', 'Satuan', 'Harga Satuan', 'Total Biaya', 'Dicatat Oleh'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontWeight('bold').setFontSize(9).setBackground('#2F5597').setFontColor('#FFFFFF')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(3, 28);
  sheet.setFrozenRows(3);

  sheet.getRange('A4:A500').setNumberFormat('yyyy-mm-dd');
  sheet.getRange('E4:E500').setNumberFormat('#,##0.##');
  sheet.getRange('G4:H500').setNumberFormat('"Rp"#,##0');

  autoFitColumns(sheet, 9);
}

/* -------------------------------------------------------------------------- */
/* TAB: REKAP BULANAN                                                         */
/* -------------------------------------------------------------------------- */
function setupRekapBulananSheet(sheet) {
  sheet.clear();
  sheet.getRange('A1:G1').merge()
    .setValue('REKAPITULASI KEUANGAN BULANAN — ZADA KARYA PRODUCTION')
    .setFontSize(12).setFontWeight('bold').setFontColor('#FFFFFF').setBackground('#1F497D')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(1, 35);

  var headers = [
    'Bulan', 'Total Kas Masuk', 'Total Kas Keluar', 'Arus Kas Bersih',
    'Total Nilai Order', 'Estimasi Laba Order', 'Sisa Piutang'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontWeight('bold').setFontSize(9).setBackground('#2F5597').setFontColor('#FFFFFF')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(3, 28);
  sheet.setFrozenRows(3);

  var currentYear = new Date().getFullYear();
  var rekapRows = [];

  for (var m = 1; m <= 12; m++) {
    var r = 3 + m;
    rekapRows.push([
      '=DATE(' + currentYear + ',' + m + ',1)',
      '=SUMIFS(\'Arus Kas\'!$F$4:$F$503,\'Arus Kas\'!$A$4:$A$503,">="&A' + r + ',\'Arus Kas\'!$A$4:$A$503,"<"&EDATE(A' + r + ',1))',
      '=SUMIFS(\'Arus Kas\'!$G$4:$G$503,\'Arus Kas\'!$A$4:$A$503,">="&A' + r + ',\'Arus Kas\'!$A$4:$A$503,"<"&EDATE(A' + r + ',1))',
      '=B' + r + '-C' + r,
      '=SUMIFS(\'Data Order\'!$G$4:$G$303,\'Data Order\'!$A$4:$A$303,">="&A' + r + ',\'Data Order\'!$A$4:$A$303,"<"&EDATE(A' + r + ',1))',
      '=SUMIFS(\'Data Order\'!$K$4:$K$303,\'Data Order\'!$A$4:$A$303,">="&A' + r + ',\'Data Order\'!$A$4:$A$303,"<"&EDATE(A' + r + ',1))',
      '=SUMIFS(\'Data Order\'!$L$4:$L$303,\'Data Order\'!$A$4:$A$303,">="&A' + r + ',\'Data Order\'!$A$4:$A$303,"<"&EDATE(A' + r + ',1))'
    ]);
  }

  sheet.getRange(4, 1, rekapRows.length, headers.length).setFormulas(rekapRows);
  sheet.getRange('A4:A15').setNumberFormat('mmmm yyyy');
  sheet.getRange('B4:G15').setNumberFormat('"Rp"#,##0');

  // Baris Total Tahunan
  sheet.getRange(16, 1).setValue('TOTAL TAHUN ' + currentYear).setFontWeight('bold').setBackground('#F2F2F2');
  for (var col = 2; col <= 7; col++) {
    var colLetter = String.fromCharCode(64 + col);
    sheet.getRange(16, col).setFormula('=SUM(' + colLetter + '4:' + colLetter + '15)')
      .setFontWeight('bold').setBackground('#F2F2F2').setNumberFormat('"Rp"#,##0');
  }

  autoFitColumns(sheet, 7);
}

/* -------------------------------------------------------------------------- */
/* TAB: DASHBOARD                                                             */
/* -------------------------------------------------------------------------- */
function setupDashboardSheet(sheet, rekapSheet) {
  sheet.clear();
  var charts = sheet.getCharts();
  for (var i = 0; i < charts.length; i++) {
    sheet.removeChart(charts[i]);
  }

  // Header Banner
  sheet.getRange('A1:H1').merge()
    .setValue('DASHBOARD KEUANGAN & OPERASIONAL — ZADA KARYA PRODUCTION')
    .setFontSize(13).setFontWeight('bold').setFontColor('#FFFFFF').setBackground('#1F497D')
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(1, 40);

  // KARTU BARIS 1: KAS (Baris 3 & 4)
  var cardRow1 = [
    { colL: 'A', colR: 'B', title: 'Saldo Kas Saat Ini', formula: '=SUM(\'Arus Kas\'!F4:F)-SUM(\'Arus Kas\'!G4:G)', bg: '#EBF3FB', text: '#1F497D' },
    { colL: 'C', colR: 'D', title: 'Total Kas Masuk Bulan Ini', formula: '=SUMIFS(\'Arus Kas\'!F4:F,\'Arus Kas\'!A4:A,">="&DATE(YEAR(TODAY()),MONTH(TODAY()),1),\'Arus Kas\'!A4:A,"<="&EOMONTH(TODAY(),0))', bg: '#EAF8ED', text: '#1E7E34' },
    { colL: 'E', colR: 'F', title: 'Total Kas Keluar Bulan Ini', formula: '=SUMIFS(\'Arus Kas\'!G4:G,\'Arus Kas\'!A4:A,">="&DATE(YEAR(TODAY()),MONTH(TODAY()),1),\'Arus Kas\'!A4:A,"<="&EOMONTH(TODAY(),0))', bg: '#FDECEC', text: '#C0504D' },
    { colL: 'G', colR: 'H', title: 'Arus Kas Bersih Bulan Ini', formula: '=C4-E4', bg: '#F2F4F7', text: '#2A3F54' }
  ];

  for (var i = 0; i < cardRow1.length; i++) {
    var c = cardRow1[i];
    sheet.getRange(c.colL + '3:' + c.colR + '3').merge().setValue(c.title)
      .setFontSize(8).setFontWeight('bold').setFontColor('#595959').setBackground(c.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle');

    sheet.getRange(c.colL + '4:' + c.colR + '4').merge().setFormula(c.formula)
      .setFontSize(14).setFontWeight('bold').setFontColor(c.text).setBackground(c.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle').setNumberFormat('"Rp"#,##0');
  }
  sheet.setRowHeight(3, 22);
  sheet.setRowHeight(4, 34);

  // KARTU BARIS 2: ORDER & PROFIT (Baris 6 & 7)
  var cardRow2 = [
    { colL: 'A', colR: 'B', title: 'Total Sisa Piutang', formula: '=IFERROR(SUM(\'Data Order\'!L4:L303),0)', bg: '#FFF9E6', text: '#B78103' },
    { colL: 'C', colR: 'D', title: 'Total Nilai Order Masuk', formula: '=IFERROR(SUM(\'Data Order\'!G4:G303),0)', bg: '#EBF3FB', text: '#1F497D' },
    { colL: 'E', colR: 'F', title: 'Total Estimasi Laba Order', formula: '=IFERROR(SUM(\'Data Order\'!K4:K303),0)', bg: '#EAF8ED', text: '#1E7E34' },
    { colL: 'G', colR: 'H', title: 'Estimasi Margin Keuntungan', formula: '=IFERROR(IF(C7>0,E7/C7,0),0)', bg: '#F2F4F7', text: '#2A3F54', format: '0.0%' }
  ];

  for (var j = 0; j < cardRow2.length; j++) {
    var d = cardRow2[j];
    sheet.getRange(d.colL + '6:' + d.colR + '6').merge().setValue(d.title)
      .setFontSize(8).setFontWeight('bold').setFontColor('#595959').setBackground(d.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle');

    sheet.getRange(d.colL + '7:' + d.colR + '7').merge().setFormula(d.formula)
      .setFontSize(14).setFontWeight('bold').setFontColor(d.text).setBackground(d.bg)
      .setHorizontalAlignment('center').setVerticalAlignment('middle')
      .setNumberFormat(d.format || '"Rp"#,##0');
  }
  sheet.setRowHeight(6, 22);
  sheet.setRowHeight(7, 34);

  // REKAP BIAYA HPP PER KATEGORI (Baris 10-18)
  sheet.getRange('A9:D9').merge()
    .setValue('KOMPOSISI PENGELUARAN BIAYA PRODUKSI (HPP)')
    .setFontSize(9).setFontWeight('bold').setFontColor('#FFFFFF').setBackground('#2F5597')
    .setHorizontalAlignment('left').setVerticalAlignment('middle');

  sheet.getRange('A10:D10').setValues([['Kategori HPP', 'Keterangan', 'Total Biaya', 'Porsi %']])
    .setFontSize(8).setFontWeight('bold').setBackground('#D9E1F2');

  var categories = [
    ['Kain & Bahan Baku', 'Kain utama, rib, furing, kerah'],
    ['Upah CMT / Jahit', 'Upah jahit, cutting, pola, finishing'],
    ['Jasa Makloon', 'Bordir komputer, sablon DTF, plastisol'],
    ['Aksesoris & Trims', 'Kancing, resleting, label woven, hangtag'],
    ['Packing & Operasional', 'Plastik polymailer, lakban, kurir, dll']
  ];

  for (var k = 0; k < categories.length; k++) {
    var rCat = 11 + k;
    sheet.getRange('A' + rCat).setValue(categories[k][0]).setFontWeight('bold').setFontSize(8);
    sheet.getRange('B' + rCat).setValue(categories[k][1]).setFontSize(8).setFontColor('#595959');
    sheet.getRange('C' + rCat).setFormula('=IFERROR(SUMIF(\'Rincian Biaya HPP\'!C4:C500, "' + categories[k][0] + '", \'Rincian Biaya HPP\'!H4:H500), 0)')
      .setFontWeight('bold').setFontSize(8).setNumberFormat('"Rp"#,##0');
    sheet.getRange('D' + rCat).setFormula('=IFERROR(IF(C16>0, C' + rCat + '/C16, 0), 0)')
      .setFontSize(8).setNumberFormat('0.0%');
  }

  // Total HPP Baris 16
  sheet.getRange('A16:B16').merge().setValue('TOTAL BIAYA HPP')
    .setFontWeight('bold').setFontSize(8).setBackground('#F2F2F2').setHorizontalAlignment('right');
  sheet.getRange('C16').setFormula('=SUM(C11:C15)')
    .setFontWeight('bold').setFontSize(9).setFontColor('#C0504D').setBackground('#F2F2F2').setNumberFormat('"Rp"#,##0');
  sheet.getRange('D16').setFormula('=IFERROR(SUM(D11:D15), 0)')
    .setFontWeight('bold').setFontSize(8).setBackground('#F2F2F2').setNumberFormat('0.0%');

  // GRAFIK BATANG KAS MASUK VS KAS KELUAR BULANAN (Ditaruh di samping kanan)
  if (rekapSheet) {
    var chart = sheet.newChart()
      .asColumnChart()
      .addRange(rekapSheet.getRange('A3:C15'))
      .setPosition(9, 5, 0, 0)
      .setOption('title', 'Tren Arus Kas Masuk vs Keluar Bulanan')
      .setOption('legend', { position: 'top' })
      .setOption('colors', ['#1E7E34', '#C0504D'])
      .setOption('width', 520)
      .setOption('height', 240)
      .build();
    sheet.insertChart(chart);
  }

  autoFitColumns(sheet, 8);
}

/* -------------------------------------------------------------------------- */
/* DATA HANDLERS                                                              */
/* -------------------------------------------------------------------------- */
function handleSyncAll(data, ss) {
  // 1. Sinkronkan Data Order
  var orderSheet = ss.getSheetByName('Data Order');
  if (orderSheet && data.orders) {
    clearSheetDataRows(orderSheet, 4);
    if (data.orders.length > 0) {
      var orderRows = data.orders.map(function(o) {
        return [
          o.date, o.order_number, o.customer, o.product, o.qty, o.price_per_unit,
          o.total_order, o.dp, o.settlement, o.total_cost, o.estimated_profit,
          o.remaining, o.status, o.notes
        ];
      });
      orderSheet.getRange(4, 1, orderRows.length, orderRows[0].length).setValues(orderRows);
    }
  }

  // 2. Sinkronkan Rincian Biaya HPP
  var costSheet = ss.getSheetByName('Rincian Biaya HPP');
  if (costSheet && data.costs) {
    clearSheetDataRows(costSheet, 4);
    if (data.costs.length > 0) {
      var costRows = data.costs.map(function(c) {
        return [
          c.date, c.order_number, c.category, c.description,
          c.qty, c.unit, c.unit_price, c.amount, c.recorded_by
        ];
      });
      costSheet.getRange(4, 1, costRows.length, costRows[0].length).setValues(costRows);
    }
  }

  // 3. Sinkronkan Arus Kas Terpadu (Gabungan Uang Masuk Pembayaran & Uang Keluar Biaya HPP)
  var cashSheet = ss.getSheetByName('Arus Kas');
  if (cashSheet) {
    clearSheetDataRows(cashSheet, 4);
    var cashFlowEntries = [];

    // Tambah dari pembayaran pelanggan (Kas Masuk)
    if (data.payments && data.payments.length > 0) {
      data.payments.forEach(function(p) {
        cashFlowEntries.push({
          date: p.date,
          trx_no: p.invoice_number || p.order_number,
          desc: 'Pembayaran ' + (p.note || 'Pelanggan') + ' (' + p.order_number + ')',
          category: (p.note && p.note.toLowerCase().indexOf('dp') !== -1) ? 'Penjualan/DP' : 'Pelunasan',
          party: p.customer,
          inflow: p.amount,
          outflow: 0,
          method: p.method
        });
      });
    }

    // Tambah dari pengeluaran biaya HPP (Kas Keluar)
    if (data.costs && data.costs.length > 0) {
      data.costs.forEach(function(c) {
        cashFlowEntries.push({
          date: c.date,
          trx_no: c.order_number,
          desc: c.description + ' (' + c.order_number + ')',
          category: c.category,
          party: 'Vendor / Tim Produksi',
          inflow: 0,
          outflow: c.amount,
          method: 'Transfer / Kas'
        });
      });
    }

    // Urutkan transaksi kas berdasarkan tanggal (kronologis)
    cashFlowEntries.sort(function(a, b) {
      return (a.date || '').localeCompare(b.date || '');
    });

    if (cashFlowEntries.length > 0) {
      var cashRows = cashFlowEntries.map(function(entry, idx) {
        var rowNum = 4 + idx;
        return [
          entry.date, entry.trx_no, entry.desc, entry.category, entry.party,
          entry.inflow, entry.outflow,
          '=IF(A' + rowNum + '="","",SUM($F$4:F' + rowNum + ')-SUM($G$4:G' + rowNum + '))',
          entry.method
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
    orderData.date, orderData.order_number, orderData.customer, orderData.product,
    orderData.qty, orderData.price_per_unit, orderData.total_order, orderData.dp,
    orderData.settlement, orderData.total_cost, orderData.estimated_profit,
    orderData.remaining, orderData.status, orderData.notes
  ];

  var lastRow = sheet.getLastRow();
  var foundRow = -1;

  if (lastRow >= 4) {
    var orderNumbers = sheet.getRange(4, 2, lastRow - 3, 1).getValues();
    for (var i = 0; i < orderNumbers.length; i++) {
      if (orderNumbers[i][0] === o[1]) {
        foundRow = i + 4;
        break;
      }
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
    costData.qty, costData.unit, costData.unit_price, costData.amount, costData.recorded_by
  ];

  if (costSheet) {
    costSheet.appendRow(c);
  }

  // Tambahkan juga ke Arus Kas sebagai Kas Keluar
  if (cashSheet) {
    var nextRow = Math.max(cashSheet.getLastRow() + 1, 4);
    var cashRow = [
      c[0], c[1], c[3] + ' (' + c[1] + ')', c[2], 'Vendor/Produksi',
      0, c[7],
      '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
      'Kas/Transfer'
    ];
    cashSheet.appendRow(cashRow);
  }
}

function handleAddPayment(payData, ss) {
  var cashSheet = ss.getSheetByName('Arus Kas');
  if (!cashSheet) return;

  var p = Array.isArray(payData) ? payData : [
    payData.date, payData.trx_no || payData.order_number, payData.desc,
    payData.category || 'Penjualan/Pelunasan', payData.customer,
    payData.amount, 0, '', payData.method
  ];

  var nextRow = Math.max(cashSheet.getLastRow() + 1, 4);
  var rowToInsert = [
    p[0], p[1], p[2] || ('Pembayaran Pesanan ' + p[1]),
    p[3] || 'Pelunasan', p[4] || '-', p[5] || 0, 0,
    '=IF(A' + nextRow + '="","",SUM($F$4:F' + nextRow + ')-SUM($G$4:G' + nextRow + '))',
    p[8] || 'Transfer'
  ];
  cashSheet.appendRow(rowToInsert);
}

/* -------------------------------------------------------------------------- */
/* UTILITY HELPERS                                                            */
/* -------------------------------------------------------------------------- */
function getOrCreateSheet(ss, sheetName) {
  var sheet = ss.getSheetByName(sheetName);
  if (!sheet) {
    sheet = ss.insertSheet(sheetName);
  }
  return sheet;
}

function clearSheetDataRows(sheet, startRow) {
  var lastRow = sheet.getLastRow();
  var lastCol = Math.max(sheet.getLastColumn(), 1);
  if (lastRow >= startRow) {
    sheet.getRange(startRow, 1, lastRow - startRow + 1, lastCol).clearContent();
  }
}

function autoFitColumns(sheet, maxCols) {
  for (var c = 1; c <= maxCols; c++) {
    sheet.autoResizeColumn(c);
  }
}

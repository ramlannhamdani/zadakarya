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
var SCRIPT_VERSION = '2026-10-29.02';

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
  if (!lock.tryLock(20000)) return respondJSON({status: 'error', message: 'Sinkronisasi lain masih berjalan. Coba lagi sebentar.'});

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

    refreshDashboard(ss);
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
  var trend = ss.getSheetByName('Tren Dashboard');
  if (!sheet || !trend) return;
  var panels = [
    {col: 1, hint: 'A49:F52', source: 'A3:C11', title: 'arus kas', type: 'Column chart'},
    {col: 7, hint: 'G49:L52', source: 'E3:G11', title: 'laba', type: 'Line chart'}
  ];
  panels.forEach(function(panel) {
    sheet.getRange(panel.hint).breakApart().merge()
      .setValue('Grafik ' + panel.title + ' belum dipasang. Buka tab "Tren Dashboard", blok ' + panel.source
        + ', lalu Insert > Chart, pilih ' + panel.type + '. Pindahkan ke area ini. Tabel tren di atas tetap bisa dibaca.')
      .setFontSize(10).setFontColor(CLR.gray2).setBackground(CLR.gray1).setWrap(true).setVerticalAlignment('middle');
  });
  if (sheet.getCharts().length > 0) {
    var occupied = {};
    sheet.getCharts().forEach(function(chart) {
      var panel = panels[chart.getContainerInfo().getAnchorColumn() >= 7 ? 1 : 0];
      if (occupied[panel.col]) return;
      occupied[panel.col] = true;
      // Hanya arahkan ulang sumber dan posisi grafik manual; tidak membuat atau menghapus grafik.
      var updated = chart.modify().clearRanges().addRange(trend.getRange(panel.source))
        .setNumHeaders(1).setPosition(48, panel.col, 0, 0).setOption('width', 575).setOption('height', 350).build();
      sheet.updateChart(updated);
      sheet.getRange(panel.hint).clearContent().setBackground(null);
    });
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
/* Perhitungan ini diuji terhadap DashboardController dengan data yang sama. */
var DASHBOARD_HPP_CATEGORIES = ['Kain & Bahan Baku', 'Aksesoris & Trims', 'Jasa Makloon (Bordir/Sablon)', 'Upah CMT / Jahit', 'Packing & Operasional'];
var DASHBOARD_CARD_KEYS = [
  ['current_cash_balance', 'total_cash_advances', 'total_active_debt', 'kas_bersih'],
  ['this_month_cash_inflow', 'this_month_global_outflow', 'critical_receivable', 'active_receivable'],
  ['order_value', 'operating_cost', 'net_profit', 'retained_earnings'],
  ['aset_mesin', 'aset_lain', 'total_aset_investasi', 'total_equity']
];
var DASHBOARD_CARD_LABELS = [
  ['KAS RIIL', 'TALANGAN KAS', 'SISA PINJAMAN', 'KAS BERSIH'],
  ['KAS MASUK BLN INI', 'KAS KELUAR BLN INI', 'PIUTANG JT (JATUH TEMPO)', 'PIUTANG BERJALAN'],
  ['TOTAL NILAI ORDER', 'BIAYA OPERASIONAL', 'LABA BERSIH & MARGIN', 'ALOKASI LABA'],
  ['ASET MESIN', 'ASET LAIN', 'TOTAL ASET & INVESTASI', 'TOTAL EKUITAS']
];

function dashboardDate(value) {
  if (!value) return '';
  if (typeof value.getFullYear === 'function') {
    if (typeof Utilities !== 'undefined') return Utilities.formatDate(value, 'Asia/Jakarta', 'yyyy-MM-dd');
    return value.getFullYear() + '-' + ('0' + (value.getMonth() + 1)).slice(-2) + '-' + ('0' + value.getDate()).slice(-2);
  }
  return String(value).slice(0, 10);
}

function dashboardRows(ss, name, columns) {
  var sheet = ss.getSheetByName(name);
  return !sheet || sheet.getLastRow() < 4 ? [] : sheet.getRange(4, 1, sheet.getLastRow() - 3, columns).getValues();
}

function readDashboardSource(ss) {
  return {
    orders: dashboardRows(ss, 'Data Order', 22), costs: dashboardRows(ss, 'Rincian Biaya HPP', 10),
    cash: dashboardRows(ss, 'Arus Kas', 10), convection: dashboardRows(ss, 'Beban Konveksi', 10),
    personal: dashboardRows(ss, 'Beban Pribadi', 10), loans: dashboardRows(ss, 'Pinjaman', 11),
    assets: dashboardRows(ss, 'Aset', 13)
  };
}

function calculateDashboardData(source, today) {
  today = dashboardDate(today);
  var ym = today.slice(0, 7);
  var num = function(value) { return Number(value) || 0; };
  var sum = function(rows, fn) { return rows.reduce(function(total, row) { return total + fn(row); }, 0); };
  var orders = source.orders.filter(function(row) { return !!row[1]; }).map(function(row) {
    var status = row[13] === 'Selesai' ? 'completed' : (row[13] === 'Dibatalkan' ? 'cancelled' : 'active');
    var paid = row[20] !== '' && row[20] !== undefined ? num(row[20]) : num(row[7]) + num(row[8]);
    return {id: num(String(row[15] || '').replace('ORD-', '')), order_number: row[1], customer: row[2] || 'Pelanggan',
      status: status, total: num(row[6]), paid: paid, cost: num(row[9]), remaining: Math.max(0, num(row[6]) - paid),
      deadline: dashboardDate(row[16]), created_at: String(row[19] || dashboardDate(row[0])),
      progress_pct: num(row[18]), stage_name: row[17] || '', payment_status: row[21] || (num(row[11]) > 0 ? 'partial' : 'paid')};
  });
  var byNumber = {};
  orders.forEach(function(order) { byNumber[order.order_number] = order; });
  var included = orders.filter(function(order) { return order.status !== 'cancelled'; });
  var costs = source.costs.filter(function(row) { return !!row[9]; });
  var profitCosts = costs.filter(function(row) { var order = byNumber[row[1]]; return order && order.status !== 'cancelled'; });
  var convection = source.convection.filter(function(row) { return !!row[8]; });
  var personal = source.personal.filter(function(row) { return !!row[8]; });
  var thisMonthPersonal = personal.filter(function(row) { return dashboardDate(row[0]).slice(0, 7) === ym; });
  var cash = source.cash.filter(function(row) { return !!row[9]; });
  var monthCash = cash.filter(function(row) { return dashboardDate(row[0]).slice(0, 7) === ym; });
  var active = included.filter(function(order) { return order.status === 'active'; });
  var critical = included.filter(function(order) { return order.remaining > 0 && (order.status === 'completed' || (order.deadline && order.deadline <= today)); });
  var f = {};
  f.current_cash_balance = sum(cash, function(row) { return num(row[5]) - num(row[6]); });
  f.total_cash_advances = sum(active.filter(function(order) { return order.payment_status !== 'paid'; }), function(order) { return Math.max(0, order.cost - order.paid); });
  f.total_active_debt = sum(source.loans.filter(function(row) { return row[10] && row[7] !== 'Lunas'; }), function(row) { return num(row[5]); });
  f.kas_bersih = f.current_cash_balance - f.total_cash_advances;
  f.this_month_cash_inflow = sum(monthCash, function(row) { return num(row[5]); });
  f.this_month_global_outflow = sum(monthCash, function(row) { return num(row[6]); });
  f.critical_receivable = sum(critical, function(order) { return order.remaining; });
  f.active_receivable = sum(active.filter(function(order) { return !order.deadline || order.deadline > today; }), function(order) { return order.remaining; });
  f.order_value = sum(included, function(order) { return order.total; });
  f.total_hpp = sum(profitCosts, function(row) { return num(row[7]); });
  f.convection_expenses_total = sum(convection, function(row) { return num(row[4]); });
  f.personal_expenses_total = sum(personal, function(row) { return num(row[4]); });
  f.operating_cost = f.total_hpp + f.convection_expenses_total;
  f.gross_profit = f.order_value - f.total_hpp;
  f.net_profit = f.gross_profit - f.convection_expenses_total;
  var margin = f.order_value > 0 ? f.net_profit / f.order_value * 100 : 0;
  f.profit_margin_pct = (margin < 0 ? -1 : 1) * Math.round(Math.abs(margin) * 10) / 10;
  f.retained_earnings = f.net_profit - f.personal_expenses_total;
  f.aset_mesin = sum(source.assets.filter(function(row) { return row[12] && /Mesin Jahit|Mesin Potong|Sablon, Heatpress|Setrika Uap/.test(row[3]); }), function(row) { return num(row[7]); });
  f.total_aset_investasi = sum(source.assets.filter(function(row) { return !!row[12]; }), function(row) { return num(row[7]); });
  f.aset_lain = f.total_aset_investasi - f.aset_mesin;
  f.total_equity = f.current_cash_balance + sum(included, function(order) { return order.remaining; }) + f.total_aset_investasi - f.total_active_debt;
  f.total_wip_pelunasan = sum(active.filter(function(order) { return order.payment_status !== 'paid'; }), function(order) { return order.remaining; });
  f.total_prive_month = sum(thisMonthPersonal, function(row) { return num(row[4]); });
  var categoryTotals = {};
  profitCosts.forEach(function(row) {
    var key = DASHBOARD_HPP_CATEGORIES.indexOf(row[2]) >= 0 ? row[2] : 'Biaya Lainnya';
    categoryTotals[key] = (categoryTotals[key] || 0) + num(row[7]);
  });
  var composition = DASHBOARD_HPP_CATEGORIES.slice();
  if ((categoryTotals['Biaya Lainnya'] || 0) > 0) composition.push('Biaya Lainnya');
  composition = composition.map(function(name) { var amount = categoryTotals[name] || 0;
    return {name: name, amount: amount, percentage: f.total_hpp > 0 ? Math.round(amount / f.total_hpp * 1000) / 10 : 0}; });
  critical.sort(function(a, b) { return a.deadline.localeCompare(b.deadline) || a.id - b.id; });
  active.sort(function(a, b) { return b.created_at.localeCompare(a.created_at) || b.id - a.id; });
  thisMonthPersonal.sort(function(a, b) { return dashboardDate(b[0]).localeCompare(dashboardDate(a[0])) || num(b[9]) - num(a[9]); });
  var trend = [];
  for (var i = -7; i <= 0; i++) {
    var monthIndex = num(today.slice(0, 4)) * 12 + num(today.slice(5, 7)) - 1 + i;
    var year = Math.floor(monthIndex / 12);
    var monthNumber = monthIndex % 12;
    var key = year + '-' + ('0' + (monthNumber + 1)).slice(-2);
    var inMonth = function(row) { return dashboardDate(row[0]).slice(0, 7) === key; };
    var gross = sum(included.filter(function(order) { return order.created_at.slice(0, 7) === key; }), function(order) { return order.total; })
      - sum(profitCosts.filter(inMonth), function(row) { return num(row[7]); });
    trend.push({label: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'][monthNumber], full: MONTHS_ID[monthNumber] + ' ' + year,
      kas_masuk: sum(cash.filter(inMonth), function(row) { return num(row[5]); }),
      kas_keluar: sum(cash.filter(inMonth), function(row) { return num(row[6]); }),
      estimasi_laba: gross, laba_bersih: gross - sum(convection.filter(inMonth), function(row) { return num(row[4]); })});
  }
  return {financials: f, critical_orders: critical.slice(0, 6), wip_orders: active.slice(0, 6),
    cost_composition: composition, personal_expenses: thisMonthPersonal.slice(0, 7).map(function(row) {
      return {reference_no: row[1], date: dashboardDate(row[0]), title: row[3], category: row[2], amount: num(row[4])};
    }), monthly_trend: trend};
}

function setupDashboardSheet(sheet, rekapSheet) {
  if (sheet.getMaxColumns() < 12) sheet.insertColumnsAfter(sheet.getMaxColumns(), 12 - sheet.getMaxColumns());
  sheet.getRange(1, 1, sheet.getMaxRows(), 12).breakApart();
  sheet.clear();
  [105, 90, 105, 100, 100, 100, 105, 90, 105, 100, 100, 100].forEach(function(width, i) { sheet.setColumnWidth(i + 1, width); });
  sheet.setRowHeight(1, 48);
  sheet.getRange('A1:L1').merge().setValue('DASHBOARD KEUANGAN — ZADA KARYA PRODUCTION')
    .setFontFamily('Arial').setFontSize(14).setFontWeight('bold').setFontColor(CLR.white).setBackground(CLR.navy)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setRowHeight(2, 5);
  sheet.getRange('A2:L2').setBackground('#F59E0B');
  for (var group = 0; group < 4; group++) {
    var labelRow = 3 + group * 3;
    sheet.setRowHeight(labelRow, 28);
    sheet.setRowHeight(labelRow + 1, 48);
    sheet.setRowHeight(labelRow + 2, 38);
    for (var card = 0; card < 4; card++) {
      var col = card * 3 + 1;
      var accent = card === 3 && group === 0 ? '#047857' : (card === 3 && group === 3 ? '#0E7490' : CLR.navy);
      var solid = card === 3 && (group === 0 || group === 2 || group === 3);
      sheet.getRange(labelRow, col, 1, 3).merge().setValue(DASHBOARD_CARD_LABELS[group][card])
        .setFontSize(9).setFontWeight('bold').setFontColor(CLR.white).setBackground(accent)
        .setHorizontalAlignment('left').setVerticalAlignment('middle').setWrap(true);
      sheet.getRange(labelRow + 1, col, 1, 3).merge().setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0')
        .setFontSize(18).setFontWeight('bold').setFontColor(solid ? CLR.white : accent).setBackground(solid ? accent : CLR.white).setVerticalAlignment('middle');
      sheet.getRange(labelRow + 2, col, 1, 3).merge().setFontSize(8).setFontColor(solid ? CLR.white : CLR.gray2)
        .setVerticalAlignment('middle').setWrap(true).setBackground(solid ? accent : CLR.gray1);
    }
  }
  sheet.setRowHeight(15, 16);
  dashboardPanel(sheet, 16, 1, 'PIUTANG KRITIS & JATUH TEMPO');
  dashboardPanel(sheet, 16, 7, 'STATUS ORDER BERJALAN (WIP)');
  dashboardTable(sheet, 17, 1, ['No. Order', 'Pelanggan', 'Nilai Tagihan', 'Jatuh Tempo', 'Status'], [1, 1, 1, 1, 2], 6);
  dashboardTable(sheet, 17, 7, ['No. Order', 'Pelanggan', 'Progres Produksi', 'Nilai Pelunasan', 'Status Tahap'], [1, 1, 1, 1, 2], 6);
  dashboardPanel(sheet, 26, 1, 'KOMPOSISI BIAYA PRODUKSI (HPP)');
  dashboardPanel(sheet, 26, 7, 'BEBAN PRIBADI PER BULAN (OWNER / PRIVE)');
  dashboardTable(sheet, 27, 1, ['Kategori HPP', 'Total Biaya', 'Porsi Biaya', ''], [3, 1, 1, 1], 7);
  sheet.getRange('E27:F27').merge();
  dashboardTable(sheet, 27, 7, ['No. Transaksi', 'Tanggal', 'Deskripsi Pengeluaran', 'Kategori', 'Jumlah (Rp)'], [1, 1, 2, 1, 1], 7);
  dashboardPanel(sheet, 37, 1, 'TREN ARUS KAS BULANAN');
  dashboardPanel(sheet, 37, 7, 'TREN LABA BERSIH & ESTIMASI LABA');
  dashboardTable(sheet, 38, 1, ['Bulan', 'Kas Masuk', 'Kas Keluar'], [2, 2, 2], 8);
  dashboardTable(sheet, 38, 7, ['Bulan', 'Estimasi Laba Kotor', 'Laba Bersih'], [2, 2, 2], 8);
  // Grafik buatan pemilik membutuhkan ruang sendiri agar tabel tidak tertutup.
  for (var gr = 48; gr <= 60; gr++) { sheet.setRowHeight(gr, ROW_H); }
  sheet.getRange('A62:L62').merge().setFontSize(9).setFontColor(CLR.gray2).setHorizontalAlignment('right');
  sheet.setRowHeight(62, 28);
  sheet.setFrozenRows(2);
  refreshDashboard(sheet.getParent());
}

function dashboardPanel(sheet, row, col, title) {
  sheet.setRowHeight(row, 30);
  sheet.getRange(row, col, 1, 6).merge().setValue(title).setBackground(CLR.navy).setFontColor(CLR.white)
    .setFontSize(10).setFontWeight('bold').setVerticalAlignment('middle');
}

function dashboardTable(sheet, header, firstCol, labels, spans, count) {
  sheet.setRowHeight(header, ROW_H);
  for (var offset = 0; offset <= count; offset++) {
    sheet.setRowHeight(header + offset, ROW_H);
    var col = firstCol;
    spans.forEach(function(span, i) {
      var range = sheet.getRange(header + offset, col, 1, span);
      if (span > 1) range.merge();
      range.setFontSize(9).setWrap(true).setVerticalAlignment('middle')
        .setBackground(offset === 0 ? CLR.blueLight : (offset % 2 ? CLR.rowOdd : CLR.rowEven));
      if (offset === 0) range.setValue(labels[i]).setFontWeight('bold').setFontColor(CLR.navy);
      col += span;
    });
  }
}

function writeDashboardRows(sheet, startRow, firstCol, spans, count, rows, moneyColumns) {
  var values = [];
  for (var offset = 0; offset < count; offset++) {
    var row = [];
    spans.forEach(function(span, i) {
      row.push(rows[offset] ? rows[offset][i] : '');
      for (var extra = 1; extra < span; extra++) row.push('');
    });
    values.push(row);
  }
  // Satu tulis per tabel menghindari ratusan panggilan jaringan saat setiap transaksi disinkronkan.
  sheet.getRange(startRow, firstCol, count, 6).setValues(values);
  var col = firstCol;
  spans.forEach(function(span, i) {
    if (moneyColumns.indexOf(i) >= 0) sheet.getRange(startRow, col, count, span)
      .setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0').setHorizontalAlignment('right');
    col += span;
  });
}

function dashboardTotal(sheet, row, col, label, value) {
  sheet.getRange(row, col, 1, 4).merge().setValue(label);
  sheet.getRange(row, col + 4, 1, 2).merge().setValue(value).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  sheet.getRange(row, col, 1, 6).setBackground(CLR.navy).setFontColor(CLR.white).setFontSize(9).setFontWeight('bold');
  sheet.setRowHeight(row, 30);
}

function refreshDashboard(ss, day) {
  var sheet = ss.getSheetByName('Dashboard');
  if (!sheet) return;
  var today = dashboardDate(day || new Date());
  var data = calculateDashboardData(readDashboardSource(ss), today);
  var f = data.financials;
  DASHBOARD_CARD_KEYS.forEach(function(keys, row) {
    keys.forEach(function(key, col) {
      sheet.getRange(4 + row * 3, 1 + col * 3).setValue(f[key]);
    });
  });
  var details = [
    ['Saldo kas masuk − keluar', 'HPP belum tertutup seluruh pembayaran', 'Sisa pokok pinjaman pihak luar', 'Kas riil − talangan (estimasi)'],
    ['Pembayaran pelanggan + pinjaman', 'HPP + ops + prive + cicilan', 'Selesai / deadline sampai hari ini', 'Pesanan aktif belum jatuh tempo'],
    ['Seluruh pesanan selain dibatalkan', 'HPP: ' + dashboardRupiah(f.total_hpp) + '\nOps. Konveksi: ' + dashboardRupiah(f.convection_expenses_total),
      'Margin: ' + f.profit_margin_pct + '%\nLaba kotor: ' + dashboardRupiah(f.gross_profit), 'Prive: ' + dashboardRupiah(f.personal_expenses_total) + '\nSisa laba ditahan untuk usaha'],
    ['Nilai buku aset mesin', 'Nilai buku aset selain mesin', 'Total nilai buku seluruh aset', 'Kas + piutang + aset − utang']
  ];
  details.forEach(function(row, i) { row.forEach(function(text, j) { sheet.getRange(5 + i * 3, 1 + j * 3).setValue(text); }); });
  writeDashboardRows(sheet, 18, 1, [1, 1, 1, 1, 2], 6, data.critical_orders.map(function(o) {
    return [o.order_number, o.customer, o.remaining, o.deadline ? o.deadline.split('-').reverse().join('/') : '—', o.status === 'completed' ? 'Siap Tagih' : 'Belum lunas'];
  }), [2]);
  writeDashboardRows(sheet, 18, 7, [1, 1, 1, 1, 2], 6, data.wip_orders.map(function(o) {
    return [o.order_number, o.customer, o.progress_pct + '% tahap selesai', o.remaining, o.stage_name];
  }), [3]);
  dashboardTotal(sheet, 24, 1, 'TOTAL SELURUH PIUTANG KRITIS', f.critical_receivable);
  dashboardTotal(sheet, 24, 7, 'TOTAL SELURUH WIP', f.total_wip_pelunasan);
  writeDashboardRows(sheet, 28, 1, [3, 1, 1, 1], 7, data.cost_composition.map(function(c, i) {
    var row = 28 + i;
    return [c.name, c.amount, '=IF(D' + row + '>0,SPARKLINE(D' + row + ',{"charttype","bar";"max",MAX(1,$E$35);"color1","#2563EB"}),"")', c.percentage + '%'];
  }), [1]);
  writeDashboardRows(sheet, 28, 7, [1, 1, 2, 1, 1], 7, data.personal_expenses.map(function(p) {
    return [p.reference_no, p.date.split('-').reverse().join('/'), p.title, p.category, p.amount];
  }), [4]);
  dashboardTotal(sheet, 35, 1, 'TOTAL HPP — ' + (f.total_hpp > 0 ? '100%' : '0%'), f.total_hpp);
  dashboardTotal(sheet, 35, 7, 'TOTAL PRIVE BULAN INI', f.total_prive_month);
  writeDashboardRows(sheet, 39, 1, [2, 2, 2], 8, data.monthly_trend.map(function(t) { return [t.full, t.kas_masuk, t.kas_keluar]; }), [1, 2]);
  writeDashboardRows(sheet, 39, 7, [2, 2, 2], 8, data.monthly_trend.map(function(t) { return [t.full, t.estimasi_laba, t.laba_bersih]; }), [1, 2]);
  var trendSheet = getOrCreateSheet(ss, 'Tren Dashboard');
  trendSheet.getRange('A3:C3').setValues([['Bulan', 'Kas Masuk', 'Kas Keluar']]);
  trendSheet.getRange('E3:G3').setValues([['Bulan', 'Estimasi Laba Kotor', 'Laba Bersih']]);
  trendSheet.getRange(4, 1, 8, 3).setValues(data.monthly_trend.map(function(t) { return [t.full, t.kas_masuk, t.kas_keluar]; }));
  trendSheet.getRange(4, 5, 8, 3).setValues(data.monthly_trend.map(function(t) { return [t.full, t.estimasi_laba, t.laba_bersih]; }));
  trendSheet.getRange('A1:G1').breakApart().merge().setValue('SUMBER GRAFIK DASHBOARD — 8 BULAN TERAKHIR');
  trendSheet.getRange('B4:C11').setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  trendSheet.getRange('F4:G11').setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  trendSheet.setColumnWidths(1, 7, 155);
  sheet.getRange('A62').setValue('Data per ' + today + ' · Delapan bulan terakhir · Prive terpisah dari laba operasional');
  buildCashChart(ss);
  return data;
}

function dashboardRupiah(value) {
  return (value < 0 ? '-Rp ' : 'Rp ') + String(Math.abs(value)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function onOpen() {
  SpreadsheetApp.getUi().createMenu('Zada Karya').addItem('Segarkan Dashboard', 'refreshDashboardMenu').addToUi();
  refreshDashboardWithLock(SpreadsheetApp.getActiveSpreadsheet());
}

function refreshDashboardMenu() { refreshDashboardWithLock(SpreadsheetApp.getActiveSpreadsheet()); }

function refreshDashboardWithLock(ss) {
  var lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) return;
  try { refreshDashboard(ss); } finally { lock.releaseLock(); }
}

function onEdit(e) {
  if (e && e.range && ['Data Order', 'Rincian Biaya HPP', 'Arus Kas', 'Beban Konveksi', 'Beban Pribadi', 'Pinjaman', 'Aset'].indexOf(e.range.getSheet().getName()) >= 0) {
    refreshDashboardWithLock(e.source);
  }
}


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
  sheet.setColumnWidth(13, 145); // M: Realisasi DP vs HPP
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
    'Total HPP', 'Estimasi Laba', 'Sisa Tagihan', 'Realisasi DP vs HPP', 'Status', 'Catatan'
  ];
  sheet.getRange(3, 1, 1, headers.length).setValues([headers])
    .setFontSize(9).setFontWeight('bold')
    .setFontColor(CLR.white).setBackground(CLR.blue)
    .setHorizontalAlignment('center').setVerticalAlignment('middle');
  sheet.setFrozenRows(3);

  setupIdColumn(sheet, 16);
  if (sheet.getMaxColumns() < 22) { sheet.insertColumnsAfter(sheet.getMaxColumns(), 22 - sheet.getMaxColumns()); }
  sheet.getRange('Q3:V3').setValues([['Deadline', 'Tahap', 'Progres', 'Dibuat Pada', 'Pembayaran Diterima', 'Status Pembayaran']]);
  sheet.hideColumns(17, 6);

  // Format dipasang untuk seluruh kolom, bukan sampai baris tertentu.
  var rows = dataRowCount(sheet);
  sheet.getRange(4, 1, rows, 1).setNumberFormat('dd-mmm-yy');
  sheet.getRange(4, 5, rows, 1).setNumberFormat('#,##0').setHorizontalAlignment('center');
  sheet.getRange(4, 6, rows, 7).setNumberFormat('"Rp"#,##0');
  // Estimasi laba bisa minus kalau HPP melampaui nilai order.
  sheet.getRange(4, 11, rows, 1).setNumberFormat('"Rp"#,##0;[Red]-"Rp"#,##0');
  // Realisasi DP vs HPP: Positif (+) Hijau = Aman tercover DP, Negatif (-) Merah = Nombok kas
  sheet.getRange(4, 13, rows, 1).setNumberFormat('+"Rp"#,##0;[Red]-"Rp"#,##0;"Rp"0');
  sheet.getRange(4, 14, rows, 1).setHorizontalAlignment('center').setWrap(false); // Status
  sheet.getRange(4, 15, rows, 1).setWrap(true); // Catatan

  applyRowStripes(sheet, 16);

  // Sorotan visual: Realisasi DP vs HPP (> 0 hijau/aman, < 0 merah/nombok), dan Status jika Selesai
  try {
    var dpHppRange = sheet.getRange(4, 13, rows, 1);
    var statusRange = sheet.getRange(4, 14, rows, 1);
    var ruleSurplus = SpreadsheetApp.newConditionalFormatRule()
      .whenNumberGreaterThan(0)
      .setBackground(CLR.greenCard)
      .setFontColor(CLR.green)
      .setBold(true)
      .setRanges([dpHppRange])
      .build();
    var ruleDefisit = SpreadsheetApp.newConditionalFormatRule()
      .whenNumberLessThan(0)
      .setBackground(CLR.redCard)
      .setFontColor(CLR.red)
      .setBold(true)
      .setRanges([dpHppRange])
      .build();
    var ruleSelesai = SpreadsheetApp.newConditionalFormatRule()
      .whenTextContains('Selesai')
      .setBackground(CLR.greenCard)
      .setFontColor(CLR.green)
      .setBold(true)
      .setRanges([statusRange])
      .build();
    sheet.setConditionalFormatRules([ruleSurplus, ruleDefisit, ruleSelesai]);
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
  sheet.getRange('J3').setValue('Urutan');
  sheet.hideColumns(10);

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
  sheet.getRange('J3').setValue('Urutan');
  sheet.hideColumns(10);

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
          (o.dp_vs_hpp !== undefined ? o.dp_vs_hpp : ((o.dp || 0) - (o.total_cost || 0))),
          o.status, o.notes,
          o.id || '', o.deadline ? new Date(o.deadline + 'T00:00:00+07:00') : '',
          o.stage_name || '', o.progress_pct || 0, o.created_at || o.date || '',
          o.amount_paid !== undefined ? o.amount_paid : (o.dp || 0) + (o.settlement || 0), o.payment_status || ''
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
        var isDP = p.type ? p.type === 'dp' : p.category === 'Penjualan/DP';
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
          e.id || '', e.sort_id || 0
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
          e.id || '', e.sort_id || 0
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

  refreshDashboard(ss);
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
    (orderData.dp_vs_hpp !== undefined ? orderData.dp_vs_hpp : ((orderData.dp || 0) - (orderData.total_cost || 0))),
    orderData.status, orderData.notes,
    orderData.id || '', orderData.deadline ? new Date(orderData.deadline + 'T00:00:00+07:00') : '',
    orderData.stage_name || '', orderData.progress_pct || 0, orderData.created_at || orderData.date || '',
    orderData.amount_paid !== undefined ? orderData.amount_paid : (orderData.dp || 0) + (orderData.settlement || 0), orderData.payment_status || ''
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
      expId, expData.sort_id || 0
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
  refreshDashboard(ss);
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

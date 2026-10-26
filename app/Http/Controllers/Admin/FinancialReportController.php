<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConvectionAsset;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function index(Request $request): View
    {
        // 1. DATA PENDAPATAN & PENJUALAN
        $totalSales = (int) Order::sum('grand_total');
        $totalPaidByCustomer = (int) Payment::sum('amount');
        $totalReceivables = (int) Order::all()->sum('remaining');

        // 2. HARGA POKOK PENJUALAN (HPP)
        $totalHpp = (int) OrderCost::sum('amount');
        $grossProfit = $totalSales - $totalHpp;
        $grossMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 1) : 0.0;

        // 3. BEBAN OPERASIONAL
        $totalOpexConvection = (int) OperationalExpense::where('expense_type', OperationalExpense::TYPE_CONVECTION)->sum('amount');
        $totalPrive = (int) OperationalExpense::where('expense_type', OperationalExpense::TYPE_PERSONAL)->sum('amount');
        $netOperatingIncome = $grossProfit - $totalOpexConvection;
        $netRetainedEarnings = $netOperatingIncome - $totalPrive;

        // 4. PINJAMAN / DANA TALANGAN
        $totalLoanIn = (int) Loan::sum('amount');
        $totalLoanRepaid = (int) LoanPayment::sum('amount');
        $totalLoanRemaining = (int) Loan::all()->sum(fn (Loan $l) => $l->remaining_amount);

        // 5. SALDO KAS & BANK (NET CASH POSITION)
        $totalCashIn = $totalPaidByCustomer + $totalLoanIn;
        $totalCashOut = $totalHpp + $totalOpexConvection + $totalPrive + $totalLoanRepaid;
        $netCash = $totalCashIn - $totalCashOut;

        // 6. ASET TETAP KONVEKSI (FIXED ASSETS & VALUATION)
        $assetCategories = [
            'machinery' => [
                'name' => 'Mesin Jahit & Obras',
                'icon' => 'fa-shirt',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_MACHINERY)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_MACHINERY)->sum('quantity'),
            ],
            'cutting_finishing' => [
                'name' => 'Mesin Potong & Meja Pola',
                'icon' => 'fa-scissors',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_CUTTING_FINISHING)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_CUTTING_FINISHING)->sum('quantity'),
            ],
            'printing_embroidery' => [
                'name' => 'Sablon, Heatpress & Bordir',
                'icon' => 'fa-paint-roller',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_PRINTING_EMBROIDERY)->sum('quantity'),
            ],
            'finishing_steamer' => [
                'name' => 'Setrika Uap Boiler & Finishing',
                'icon' => 'fa-fire',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_FINISHING_STEAMER)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_FINISHING_STEAMER)->sum('quantity'),
            ],
            'office_tech' => [
                'name' => 'Komputer Desain & Elektronik',
                'icon' => 'fa-laptop',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_OFFICE_TECH)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_OFFICE_TECH)->sum('quantity'),
            ],
            'facility_vehicle' => [
                'name' => 'Fasilitas Workshop & Kendaraan',
                'icon' => 'fa-truck',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_FACILITY_VEHICLE)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_FACILITY_VEHICLE)->sum('quantity'),
            ],
            'other' => [
                'name' => 'Aset & Inventaris Lainnya',
                'icon' => 'fa-box-archive',
                'value' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_OTHER)->sum('current_value'),
                'count' => (int) ConvectionAsset::where('category', ConvectionAsset::CATEGORY_OTHER)->sum('quantity'),
            ],
        ];

        $totalFixedAssets = (int) ConvectionAsset::sum('current_value');
        $totalHistoricalAssets = (int) ConvectionAsset::sum('purchase_price');
        $totalDepreciation = max(0, $totalHistoricalAssets - $totalFixedAssets);

        // 7. NERACA POSISI KEUANGAN (SKONTRO FORMAT)
        // ASET LANCAR
        $currentAssetsTotal = $netCash + $totalReceivables;
        // TOTAL AKTIVA / ASET
        $totalAssets = $currentAssetsTotal + $totalFixedAssets;

        // KEWAJIBAN / LIABILITAS
        $totalLiabilities = $totalLoanRemaining;

        // EKUITAS / MODAL
        $netWorthValuation = $totalAssets - $totalLiabilities;
        $retainedEarnings = $netRetainedEarnings;
        $ownerInitialCapital = max(0, $netWorthValuation - $retainedEarnings);
        $totalEquity = $ownerInitialCapital + $retainedEarnings;

        // TOTAL PASIVA
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = ($totalAssets === $totalLiabilitiesAndEquity);

        return view('admin.finance.index', [
            // Laba Rugi Data
            'incomeStatement' => [
                'sales' => $totalSales,
                'paid_sales' => $totalPaidByCustomer,
                'receivables' => $totalReceivables,
                'hpp' => $totalHpp,
                'gross_profit' => $grossProfit,
                'gross_margin' => $grossMargin,
                'opex_convection' => $totalOpexConvection,
                'operating_profit' => $netOperatingIncome,
                'prive' => $totalPrive,
                'net_profit' => $netRetainedEarnings,
            ],

            // Neraca Skontro Data
            'balanceSheet' => [
                'current_assets' => [
                    'cash' => $netCash,
                    'receivables' => $totalReceivables,
                    'total' => $currentAssetsTotal,
                ],
                'fixed_assets' => [
                    'items' => $assetCategories,
                    'total' => $totalFixedAssets,
                    'historical_cost' => $totalHistoricalAssets,
                    'depreciation' => $totalDepreciation,
                ],
                'total_assets' => $totalAssets,
                'liabilities' => [
                    'loans' => $totalLoanRemaining,
                    'total' => $totalLiabilities,
                ],
                'equity' => [
                    'initial_capital' => $ownerInitialCapital,
                    'retained_earnings' => $retainedEarnings,
                    'total' => $totalEquity,
                ],
                'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
                'net_worth' => $netWorthValuation,
                'is_balanced' => $isBalanced,
            ],

            'activeTab' => $request->input('tab', 'neraca'),
        ]);
    }
}

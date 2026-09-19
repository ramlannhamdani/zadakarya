<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\GoogleSheetService;
use App\Support\GoogleAppsScriptCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpreadsheetController extends Controller
{
    public function index(GoogleSheetService $sheetService): View
    {
        return view('admin.spreadsheet.index', [
            'webhookUrl' => Setting::get('google_sheet_webhook_url', ''),
            'autoSync' => Setting::get('google_sheet_auto_sync', '1') === '1',
            'lastConnectedAt' => Setting::get('google_sheet_last_connected_at'),
            'lastSyncedAt' => Setting::get('google_sheet_last_synced_at'),
            'lastError' => Setting::get('google_sheet_last_error') ?: null,
            'lastErrorAt' => Setting::get('google_sheet_last_error_at') ?: null,
            'appScriptVersion' => GoogleAppsScriptCode::version(),
            'liveScriptVersion' => Setting::get('google_sheet_script_version') ?: null,
            'sheetUrl' => Setting::get('google_sheet_url') ?: null,
            'sheetName' => Setting::get('google_sheet_name') ?: null,
            'orderCount' => Order::count(),
            'costCount' => OrderCost::count(),
            'paymentCount' => Payment::count(),
            'scriptCode' => GoogleAppsScriptCode::getScript(),
            'isConnected' => $sheetService->isEnabled(),
        ]);
    }

    public function update(Request $request, GoogleSheetService $sheetService): RedirectResponse
    {
        $validated = $request->validate([
            'google_sheet_webhook_url' => ['nullable', 'string', 'max:500'],
            'google_sheet_auto_sync' => ['nullable', 'boolean'],
        ]);

        $url = trim($validated['google_sheet_webhook_url'] ?? '');
        Setting::set('google_sheet_webhook_url', $url);
        Setting::set('google_sheet_auto_sync', $request->boolean('google_sheet_auto_sync') ? '1' : '0');

        $message = 'Pengaturan integrasi Google Spreadsheet berhasil disimpan.';

        // Jika webhook terisi, langsung otomatis jalankan sinkronisasi awal semua data
        if (! empty($url)) {
            $syncRes = $sheetService->syncAll($url);
            if ($syncRes['success']) {
                $counts = $syncRes['counts'] ?? [];
                $message .= ' Dan seluruh data (' . ($counts['orders'] ?? 0) . ' pesanan, ' . ($counts['costs'] ?? 0) . ' biaya HPP, ' . ($counts['payments'] ?? 0) . ' pembayaran) berhasil langsung disinkronkan ke Google Sheets!';
            }
        }

        return redirect()->route('admin.spreadsheet.index')->with('success', $message);
    }

    public function test(Request $request, GoogleSheetService $sheetService): JsonResponse|RedirectResponse
    {
        $webhookUrl = $request->input('webhook_url');
        $result = $sheetService->testConnection($webhookUrl);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('admin.spreadsheet.index')->with('success', $result['message']);
        }

        return redirect()->route('admin.spreadsheet.index')->with('error', $result['message']);
    }

    /**
     * Menata ulang tab mengosongkan isinya (setiap setup tab diawali sheet.clear()),
     * jadi datanya langsung diisi ulang di sini. Dulu keduanya tombol terpisah dan
     * menekan yang ini saja membuat spreadsheet tampak kosong.
     */
    public function setup(Request $request, GoogleSheetService $sheetService): JsonResponse|RedirectResponse
    {
        $result = $sheetService->setupSheet();

        if ($result['success']) {
            $sync = $sheetService->syncAll();

            $result['message'] = $sync['success']
                ? 'Struktur dan tampilan spreadsheet ditata ulang, lalu seluruh data diisikan kembali.'
                : 'Struktur ditata ulang, tapi pengisian ulang data gagal: '.$sync['message'].' Jalankan "Sinkronkan Semua Data" secara manual.';

            $result['success'] = $sync['success'];
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.spreadsheet.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function syncAll(Request $request, GoogleSheetService $sheetService): JsonResponse|RedirectResponse
    {
        $result = $sheetService->syncAll();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            $counts = $result['counts'] ?? [];
            $msg = $result['message'] . ' (' . ($counts['orders'] ?? 0) . ' pesanan, ' . ($counts['costs'] ?? 0) . ' biaya HPP, ' . ($counts['payments'] ?? 0) . ' pembayaran)';
            return redirect()->route('admin.spreadsheet.index')->with('success', $msg);
        }

        return redirect()->route('admin.spreadsheet.index')->with('error', $result['message']);
    }
}

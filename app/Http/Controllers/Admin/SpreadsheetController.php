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
            'orderCount' => Order::count(),
            'costCount' => OrderCost::count(),
            'paymentCount' => Payment::count(),
            'scriptCode' => GoogleAppsScriptCode::getScript(),
            'isConnected' => $sheetService->isEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_sheet_webhook_url' => ['nullable', 'string', 'max:500'],
            'google_sheet_auto_sync' => ['nullable', 'boolean'],
        ]);

        $url = trim($validated['google_sheet_webhook_url'] ?? '');
        Setting::set('google_sheet_webhook_url', $url);
        Setting::set('google_sheet_auto_sync', $request->boolean('google_sheet_auto_sync') ? '1' : '0');

        return redirect()->route('admin.spreadsheet.index')
            ->with('success', 'Pengaturan integrasi Google Spreadsheet berhasil disimpan.');
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

    public function setup(Request $request, GoogleSheetService $sheetService): JsonResponse|RedirectResponse
    {
        $result = $sheetService->setupSheet();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('admin.spreadsheet.index')->with('success', $result['message']);
        }

        return redirect()->route('admin.spreadsheet.index')->with('error', $result['message']);
    }

    public function syncAll(Request $request, GoogleSheetService $sheetService): JsonResponse|RedirectResponse
    {
        $result = $sheetService->syncAll();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('admin.spreadsheet.index')->with('success', $result['message']);
        }

        return redirect()->route('admin.spreadsheet.index')->with('error', $result['message']);
    }
}

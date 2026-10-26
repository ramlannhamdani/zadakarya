<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConvectionAsset;
use App\Services\GoogleSheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConvectionAssetController extends Controller
{
    public function index(Request $request): View
    {
        $query = ConvectionAsset::query();

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('condition') && $request->condition !== 'all') {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%")
                    ->orWhere('notes', 'like', "%{$keyword}%");
            });
        }

        $assets = $query->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();

        $totalPurchase = (int) ConvectionAsset::sum('purchase_price');
        $totalCurrent = (int) ConvectionAsset::sum('current_value');

        $stats = [
            'total_items' => ConvectionAsset::count(),
            'total_units' => (int) ConvectionAsset::sum('quantity'),
            'total_purchase_price' => $totalPurchase,
            'total_current_value' => $totalCurrent,
            'total_depreciation' => max(0, $totalPurchase - $totalCurrent),
            'good_condition_count' => ConvectionAsset::where('condition', ConvectionAsset::CONDITION_GOOD)->count(),
        ];

        return view('admin.assets.index', [
            'assets' => $assets,
            'stats' => $stats,
            'categories' => ConvectionAsset::CATEGORIES,
            'conditions' => ConvectionAsset::CONDITIONS,
            'activeCategory' => $request->input('category'),
            'activeCondition' => $request->input('condition'),
        ]);
    }

    public function store(Request $request, GoogleSheetService $sheetService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(ConvectionAsset::CATEGORIES))],
            'purchase_date' => ['nullable', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['nullable', 'string', 'max:50'],
            'purchase_price' => ['required', 'integer', 'min:0'],
            'current_value' => ['nullable', 'integer', 'min:0'],
            'condition' => ['required', 'string', 'in:' . implode(',', array_keys(ConvectionAsset::CONDITIONS))],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['code'] = ConvectionAsset::generateCode();
        $validated['unit'] = $validated['unit'] ?: 'unit';
        $validated['current_value'] = $validated['current_value'] !== null ? (int) $validated['current_value'] : (int) $validated['purchase_price'];
        $validated['recorded_by'] = auth()->id();

        $asset = ConvectionAsset::create($validated);

        if ($sheetService->isAutoSync()) {
            $sheetService->syncAsset($asset);
        }

        return redirect()->route('admin.assets.index')
            ->with('success', "Aset \"{$asset->name}\" ({$asset->code}) berhasil dicatat.");
    }

    public function update(Request $request, ConvectionAsset $asset, GoogleSheetService $sheetService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(ConvectionAsset::CATEGORIES))],
            'purchase_date' => ['nullable', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['nullable', 'string', 'max:50'],
            'purchase_price' => ['required', 'integer', 'min:0'],
            'current_value' => ['nullable', 'integer', 'min:0'],
            'condition' => ['required', 'string', 'in:' . implode(',', array_keys(ConvectionAsset::CONDITIONS))],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['unit'] = $validated['unit'] ?: 'unit';
        $validated['current_value'] = $validated['current_value'] !== null ? (int) $validated['current_value'] : (int) $validated['purchase_price'];

        $asset->update($validated);

        if ($sheetService->isAutoSync()) {
            $sheetService->syncAsset($asset);
        }

        return redirect()->route('admin.assets.index')
            ->with('success', "Data aset \"{$asset->name}\" ({$asset->code}) berhasil diperbarui.");
    }

    public function destroy(ConvectionAsset $asset, GoogleSheetService $sheetService): RedirectResponse
    {
        $name = $asset->name;
        $code = $asset->code;

        if ($sheetService->isAutoSync()) {
            $sheetService->deleteAsset($asset);
        }

        $asset->delete();

        return redirect()->route('admin.assets.index')
            ->with('success', "Aset \"{$name}\" ({$code}) telah dihapus.");
    }
}

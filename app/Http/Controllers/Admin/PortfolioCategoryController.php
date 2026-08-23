<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortfolioCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortfolioCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        PortfolioCategory::firstOrCreate(
            ['slug' => Str::slug($data['name'])],
            ['name' => $data['name']]
        );

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function destroy(PortfolioCategory $category)
    {
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}

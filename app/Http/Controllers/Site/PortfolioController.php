<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Review;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        $categories = PortfolioCategory::whereHas('portfolios', fn ($q) => $q->published())->get();

        $portfolios = Portfolio::published()
            ->with('category')
            ->when($request->filled('kategori'), function ($q) use ($request) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $request->kategori));
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $reviews = Review::published()->orderBy('sort_order')->latest('review_date')->get();

        return view('site.portfolio.index', compact('portfolios', 'categories', 'reviews'));
    }

    public function show(Portfolio $portfolio)
    {
        abort_unless($portfolio->is_published, 404);

        $portfolio->load(['category', 'images']);

        return view('site.portfolio.show', [
            'portfolio' => $portfolio,
            'related' => Portfolio::published()
                ->where('id', '!=', $portfolio->id)
                ->when($portfolio->portfolio_category_id, fn ($q) => $q->where('portfolio_category_id', $portfolio->portfolio_category_id))
                ->latest()
                ->take(3)
                ->get(),
        ]);
    }
}

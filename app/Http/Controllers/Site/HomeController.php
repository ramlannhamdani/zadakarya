<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Portfolio;
use App\Models\Service;

class HomeController extends Controller
{
    public function index()
    {
        return view('site.home', [
            'services' => Service::published()->orderBy('sort_order')->take(8)->get(),
            'featuredPortfolios' => Portfolio::published()
                ->with('category')
                ->orderByDesc('is_featured')
                ->latest()
                ->take(6)
                ->get(),
            'heroPortfolios' => Portfolio::published()
                ->whereNotNull('cover_image')
                ->orderByDesc('is_featured')
                ->latest()
                ->take(4)
                ->get(),
            'articles' => Article::published()->with('category')->latest('published_at')->take(3)->get(),
        ]);
    }
}

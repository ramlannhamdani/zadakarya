<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Client;
use App\Models\Portfolio;
use App\Models\Review;
use App\Models\Service;
use App\Support\HeroDefaults;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $services = Service::published()->orderBy('sort_order')->take(8)->get();
        $reviews = Review::published()->orderBy('sort_order')->latest('review_date')->get();

        // Teks rating: setting > dihitung dari ulasan (min. 5) > bawaan.
        $ratingText = setting('hero_rating_text');

        if (! $ratingText && $reviews->count() >= 5) {
            $ratingText = number_format($reviews->avg('rating'), 1, '.', '').'/5 dari '.$reviews->count().' ulasan';
        }

        $avatars = $reviews->take(3)->values()->map(fn ($review, $i) => [
            'initial' => Str::upper(Str::substr(trim($review->author_name), 0, 1)) ?: '★',
            'color' => HeroDefaults::AVATAR_COLORS[$i] ?? HeroDefaults::AVATAR_COLORS[0],
        ])->all();

        foreach (HeroDefaults::AVATAR_COLORS as $i => $color) {
            $avatars[$i] ??= ['initial' => ['Z', 'K', 'P'][$i], 'color' => $color];
        }

        // Foto hero: upload admin > cover portfolio terbaru (agar hero tidak kosong) > ilustrasi.
        // File dicek keberadaannya supaya path yang menggantung tidak jadi gambar rusak.
        $heroImage = setting('hero_image');

        if ($heroImage && ! is_file(storage_path('app/public/'.$heroImage))) {
            $heroImage = null;
        }

        $heroPortfolio = $heroImage
            ? null
            : Portfolio::published()
                ->whereNotNull('cover_image')
                ->orderByDesc('is_featured')
                ->latest()
                ->get()
                ->first(fn ($portfolio) => is_file(storage_path('app/public/'.$portfolio->cover_image)));

        // Foto potongan (latar transparan) berdiri di atas bidang maroon;
        // foto biasa ditampilkan sebagai kartu berbingkai miring.
        $heroStyle = setting('hero_image_style') === 'cutout' ? 'cutout' : 'framed';

        return view('site.home', [
            'services' => $services,
            'panelServices' => $services->take(5),
            'featuredPortfolios' => Portfolio::published()
                ->with('category')
                ->orderByDesc('is_featured')
                ->latest()
                ->take(6)
                ->get(),
            'clients' => Client::active()->get(),
            'articles' => Article::published()->with('category')->latest('published_at')->take(3)->get(),
            'reviews' => $reviews,
            'hero' => [
                'badge' => setting('hero_badge') ?: HeroDefaults::BADGE,
                'title' => setting('hero_title') ?: HeroDefaults::TITLE,
                'title_accent' => setting('hero_title_accent') ?: HeroDefaults::TITLE_ACCENT,
                'text' => setting('hero_text') ?: HeroDefaults::TEXT,
                'rating_text' => $ratingText ?: HeroDefaults::RATING_TEXT,
                'rating_subtext' => HeroDefaults::RATING_SUBTEXT,
                'image' => $heroImage,
                'style' => $heroStyle,
                'portfolio' => $heroPortfolio,
                'avatars' => array_values($avatars),
            ],
            'heroStats' => HeroDefaults::stats(setting('hero_stats')),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Portfolio;
use App\Models\Service;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('services.index'), 'priority' => '0.9'],
            ['loc' => route('portfolio.index'), 'priority' => '0.8'],
            ['loc' => route('blog.index'), 'priority' => '0.7'],
            ['loc' => route('about'), 'priority' => '0.6'],
            ['loc' => route('consultation.create'), 'priority' => '0.9'],
            ['loc' => route('tracking.index'), 'priority' => '0.6'],
            ['loc' => route('contact'), 'priority' => '0.5'],
        ]);

        $urls = $urls
            ->merge(Service::published()->get()->map(fn ($s) => [
                'loc' => route('services.show', $s), 'lastmod' => $s->updated_at->toDateString(), 'priority' => '0.8',
            ]))
            ->merge(Portfolio::published()->get()->map(fn ($p) => [
                'loc' => route('portfolio.show', $p), 'lastmod' => $p->updated_at->toDateString(), 'priority' => '0.6',
            ]))
            ->merge(Article::published()->get()->map(fn ($a) => [
                'loc' => route('blog.show', $a), 'lastmod' => $a->updated_at->toDateString(), 'priority' => '0.6',
            ]));

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}

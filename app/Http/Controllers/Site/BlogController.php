<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $categories = ArticleCategory::whereHas('articles', fn ($q) => $q->published())->get();

        $articles = Article::published()
            ->with('category')
            ->when($request->filled('kategori'), function ($q) use ($request) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $request->kategori));
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
            })
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('site.blog.index', compact('articles', 'categories'));
    }

    public function show(Article $article)
    {
        if (!auth()->check()) {
            abort_if($article->published_at === null || $article->published_at->isFuture(), 404);
        }

        $article->load(['category', 'author']);

        return view('site.blog.show', [
            'article' => $article,
            'related' => Article::published()
                ->where('id', '!=', $article->id)
                ->when($article->article_category_id, fn ($q) => $q->where('article_category_id', $article->article_category_id))
                ->latest('published_at')
                ->take(3)
                ->get(),
            'categories' => ArticleCategory::whereHas('articles', fn ($q) => $q->published())
                ->withCount(['articles' => fn ($q) => $q->published()])
                ->get(),
            'latestArticles' => Article::published()
                ->where('id', '!=', $article->id)
                ->latest('published_at')
                ->take(4)
                ->get(),
        ]);
    }
}

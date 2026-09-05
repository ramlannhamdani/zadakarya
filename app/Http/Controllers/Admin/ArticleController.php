<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Support\Html;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $articles = Article::with(['category', 'author'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->when($request->filled('kategori'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->kategori)))
            ->when($status === 'published', fn ($q) => $q->published())
            ->when($status === 'scheduled', fn ($q) => $q->scheduled())
            ->when($status === 'draft', fn ($q) => $q->draft())
            ->latest('published_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $statusCounts = [
            'all' => Article::count(),
            'published' => Article::published()->count(),
            'scheduled' => Article::scheduled()->count(),
            'draft' => Article::draft()->count(),
        ];

        return view('admin.articles.index', [
            'articles' => $articles,
            'categories' => ArticleCategory::withCount('articles')->get(),
            'statusCounts' => $statusCounts,
            'currentStatus' => $status,
        ]);
    }

    public function create()
    {
        return view('admin.articles.form', [
            'article' => new Article,
            'categories' => ArticleCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $pick = $data['featured_image_pick'] ?? null;
        unset($data['featured_image_pick']);

        if ($request->hasFile('featured_image')) {
            [$path] = ImageUploader::store($request->file('featured_image'), 'articles');
            $data['featured_image'] = $path;
        } elseif ($pick && ($res = ImageUploader::fromGalleryId($pick, 'articles'))) {
            $data['featured_image'] = $res[0];
        }

        $data['user_id'] = auth()->id();
        Article::create($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil disimpan.');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', [
            'article' => $article,
            'categories' => ArticleCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validated($request, $article);
        $pick = $data['featured_image_pick'] ?? null;
        unset($data['featured_image_pick']);

        if ($request->hasFile('featured_image')) {
            ImageUploader::delete($article->featured_image);
            [$path] = ImageUploader::store($request->file('featured_image'), 'articles');
            $data['featured_image'] = $path;
        } elseif ($pick && ($res = ImageUploader::fromGalleryId($pick, 'articles'))) {
            ImageUploader::delete($article->featured_image);
            $data['featured_image'] = $res[0];
        } elseif ($request->boolean('remove_featured_image')) {
            ImageUploader::delete($article->featured_image);
            $data['featured_image'] = null;
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel diperbarui.');
    }

    public function destroy(Article $article)
    {
        ImageUploader::delete($article->featured_image);
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Artikel dihapus.');
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:100000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'featured_image_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'article_category_id' => ['nullable', 'exists:article_categories,id'],
            'tags_text' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['nullable', 'boolean'],
            'publish' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'status_action' => ['nullable', 'string', 'in:publish_now,schedule,draft'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['content'] = Html::clean($data['content']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();

        if ($request->status_action === 'draft') {
            $data['published_at'] = null;
        } elseif ($request->status_action === 'schedule' && $request->filled('published_at')) {
            $data['published_at'] = $request->date('published_at');
        } elseif ($request->status_action === 'publish_now') {
            $data['published_at'] = now();
        } elseif ($request->filled('published_at')) {
            $data['published_at'] = $request->date('published_at');
        } elseif ($request->has('publish')) {
            $data['published_at'] = $request->boolean('publish') ? ($article?->published_at ?? now()) : null;
        }

        unset($data['tags_text'], $data['publish'], $data['status_action']);

        return $data;
    }
}

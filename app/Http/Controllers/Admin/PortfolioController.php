<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\PortfolioImage;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        $portfolios = Portfolio::with('category')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.portfolio.index', [
            'portfolios' => $portfolios,
            'categories' => PortfolioCategory::withCount('portfolios')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.portfolio.form', [
            'portfolio' => new Portfolio,
            'categories' => PortfolioCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $pick = $data['cover_image_pick'] ?? null;
        unset($data['cover_image_pick'], $data['gallery_pick']);

        if ($request->hasFile('cover_image')) {
            [$path] = ImageUploader::store($request->file('cover_image'), 'portfolio');
            $data['cover_image'] = $path;
        } elseif ($pick && ($res = ImageUploader::fromGalleryId($pick, 'portfolio'))) {
            $data['cover_image'] = $res[0];
        }

        $portfolio = Portfolio::create($data);
        $this->storeGallery($request, $portfolio);

        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio berhasil dibuat.');
    }

    public function edit(Portfolio $portfolio)
    {
        $portfolio->load('images');

        return view('admin.portfolio.form', [
            'portfolio' => $portfolio,
            'categories' => PortfolioCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        $data = $this->validated($request, $portfolio);
        $pick = $data['cover_image_pick'] ?? null;
        unset($data['cover_image_pick'], $data['gallery_pick']);

        if ($request->hasFile('cover_image')) {
            ImageUploader::delete($portfolio->cover_image);
            [$path] = ImageUploader::store($request->file('cover_image'), 'portfolio');
            $data['cover_image'] = $path;
        } elseif ($pick && ($res = ImageUploader::fromGalleryId($pick, 'portfolio'))) {
            ImageUploader::delete($portfolio->cover_image);
            $data['cover_image'] = $res[0];
        }

        $portfolio->update($data);
        $this->storeGallery($request, $portfolio);

        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio diperbarui.');
    }

    public function destroy(Portfolio $portfolio)
    {
        ImageUploader::delete($portfolio->cover_image);
        foreach ($portfolio->images as $image) {
            ImageUploader::delete($image->image_path);
            ImageUploader::delete($image->thumb_path);
        }

        $portfolio->delete();

        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio dihapus.');
    }

    public function destroyImage(PortfolioImage $image)
    {
        ImageUploader::delete($image->image_path);
        ImageUploader::delete($image->thumb_path);
        $image->delete();

        return back()->with('success', 'Gambar dihapus.');
    }

    private function storeGallery(Request $request, Portfolio $portfolio): void
    {
        $sort = (int) $portfolio->images()->max('sort_order') + 1;

        foreach ($request->file('gallery') ?? [] as $file) {
            [$path, $thumb] = ImageUploader::store($file, 'portfolio');
            $portfolio->images()->create([
                'image_path' => $path,
                'thumb_path' => $thumb,
                'sort_order' => $sort++,
            ]);
        }

        foreach ((array) $request->input('gallery_pick', []) as $id) {
            if ($res = ImageUploader::fromGalleryId($id, 'portfolio')) {
                $portfolio->images()->create([
                    'image_path' => $res[0],
                    'thumb_path' => $res[1],
                    'sort_order' => $sort++,
                ]);
            }
        }
    }

    private function validated(Request $request, ?Portfolio $portfolio = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash', Rule::unique('portfolios', 'slug')->ignore($portfolio?->id)],
            'portfolio_category_id' => ['nullable', 'exists:portfolio_categories,id'],
            'description' => ['nullable', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_pick' => ['nullable', 'array'],
            'gallery_pick.*' => ['integer', 'exists:gallery_items,id'],
            'tags_text' => ['nullable', 'string', 'max:500'],
            'production_year' => ['nullable', 'string', 'max:10'],
            'client_name' => ['nullable', 'string', 'max:150'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_published'] = $request->boolean('is_published');
        $data['tags'] = collect(explode(',', (string) ($data['tags_text'] ?? '')))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();

        unset($data['tags_text'], $data['gallery']);

        return $data;
    }
}

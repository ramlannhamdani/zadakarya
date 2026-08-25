<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\Html;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        return view('admin.services.index', [
            'services' => Service::orderBy('sort_order')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.services.form', ['service' => new Service]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $pick = $data['featured_image_pick'] ?? null;
        unset($data['featured_image_pick']);

        if ($request->hasFile('featured_image')) {
            [$path] = ImageUploader::store($request->file('featured_image'), 'services');
            $data['featured_image'] = $path;
        } elseif ($pick && ($res = ImageUploader::fromGalleryId($pick, 'services'))) {
            $data['featured_image'] = $res[0];
        }

        Service::create($data);

        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil dibuat.');
    }

    public function edit(Service $service)
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $data = $this->validated($request, $service);
        $pick = $data['featured_image_pick'] ?? null;
        unset($data['featured_image_pick']);

        if ($request->hasFile('featured_image')) {
            ImageUploader::delete($service->featured_image);
            [$path] = ImageUploader::store($request->file('featured_image'), 'services');
            $data['featured_image'] = $path;
        } elseif ($pick && ($res = ImageUploader::fromGalleryId($pick, 'services'))) {
            ImageUploader::delete($service->featured_image);
            $data['featured_image'] = $res[0];
        }

        $service->update($data);

        return redirect()->route('admin.services.index')->with('success', 'Layanan diperbarui.');
    }

    public function destroy(Service $service)
    {
        ImageUploader::delete($service->featured_image);
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Layanan dihapus.');
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('services', 'slug')->ignore($service?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'featured_image_pick' => ['nullable', 'integer', 'exists:gallery_items,id'],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'material_info' => ['nullable', 'string', 'max:5000'],
            'production_info' => ['nullable', 'string', 'max:5000'],
            'min_order' => ['nullable', 'string', 'max:100'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['description'] = Html::clean($data['description'] ?? null);
        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['features'] = collect(preg_split('/\r?\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        unset($data['features_text']);

        return $data;
    }
}

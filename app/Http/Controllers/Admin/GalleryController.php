<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Support\ImageUploader;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index()
    {
        return view('admin.gallery.index', [
            'items' => GalleryItem::latest()->paginate(40),
        ]);
    }

    /** JSON untuk popup media picker (grid galeri di form-form admin). */
    public function picker()
    {
        return response()->json(
            GalleryItem::latest()->take(200)->get()->map(fn ($item) => [
                'id' => $item->id,
                'thumb' => asset('storage/'.($item->thumb_path ?: $item->image_path)),
            ])
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'photos.required' => 'Pilih minimal satu gambar.',
        ]);

        $created = collect();
        foreach ($request->file('photos') as $file) {
            // Thumb 800px agar tetap tajam pada kolom masonry layar retina.
            [$path, $thumb] = ImageUploader::store($file, 'gallery', 'public', 1600, 800);

            $created->push(GalleryItem::create([
                'image_path' => $path,
                'thumb_path' => $thumb,
                'uploaded_by' => auth()->id(),
            ]));
        }

        if ($request->wantsJson()) {
            return response()->json($created->map(fn ($item) => [
                'id' => $item->id,
                'thumb' => asset('storage/'.($item->thumb_path ?: $item->image_path)),
            ]));
        }

        return back()->with('success', $created->count().' gambar ditambahkan ke galeri.');
    }

    public function destroy(GalleryItem $item)
    {
        ImageUploader::delete($item->image_path);
        ImageUploader::delete($item->thumb_path);
        $item->delete();

        return back()->with('success', 'Gambar dihapus dari galeri.');
    }
}

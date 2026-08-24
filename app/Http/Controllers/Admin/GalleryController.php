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

    public function store(Request $request)
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'photos.required' => 'Pilih minimal satu gambar.',
        ]);

        foreach ($request->file('photos') as $file) {
            // Thumb 800px agar tetap tajam pada kolom masonry layar retina.
            [$path, $thumb] = ImageUploader::store($file, 'gallery', 'public', 1600, 800);

            GalleryItem::create([
                'image_path' => $path,
                'thumb_path' => $thumb,
                'uploaded_by' => auth()->id(),
            ]);
        }

        return back()->with('success', count($request->file('photos')).' gambar ditambahkan ke galeri.');
    }

    public function destroy(GalleryItem $item)
    {
        ImageUploader::delete($item->image_path);
        ImageUploader::delete($item->thumb_path);
        $item->delete();

        return back()->with('success', 'Gambar dihapus dari galeri.');
    }
}

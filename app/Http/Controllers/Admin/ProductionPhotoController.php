<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductionPhoto;
use App\Support\ImageUploader;
use App\Support\Stages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductionPhotoController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'stage_number' => ['required', 'integer', 'between:1,7'],
            'caption' => ['nullable', 'string', 'max:300'],
            'visibility' => ['required', Rule::in(['public', 'internal'])],
        ]);

        foreach ($request->file('photos') as $file) {
            [$path, $thumb] = ImageUploader::store($file, 'production/'.$order->id, 'local');

            $order->productionPhotos()->create([
                'stage_number' => $data['stage_number'],
                'image_path' => $path,
                'thumb_path' => $thumb,
                'caption' => $data['caption'] ?? null,
                'visibility' => $data['visibility'],
                'uploaded_by' => auth()->id(),
            ]);
        }

        $order->logActivity(count($request->file('photos')).' foto produksi diunggah pada tahap "'.Stages::name((int) $data['stage_number']).'"');

        return back()->with('success', 'Foto produksi berhasil diunggah.');
    }

    public function update(Request $request, ProductionPhoto $photo)
    {
        $data = $request->validate([
            'visibility' => ['required', Rule::in(['public', 'internal'])],
            'caption' => ['nullable', 'string', 'max:300'],
            'stage_number' => ['nullable', 'integer', 'between:1,7'],
        ]);

        $photo->update(array_filter([
            'visibility' => $data['visibility'],
            'caption' => $data['caption'] ?? $photo->caption,
            'stage_number' => $data['stage_number'] ?? $photo->stage_number,
        ], fn ($v) => $v !== null));

        $photo->order->logActivity('Foto produksi diubah menjadi '.($photo->visibility === 'public' ? 'Public' : 'Internal'));

        return back()->with('success', 'Foto diperbarui.');
    }

    public function destroy(ProductionPhoto $photo)
    {
        ImageUploader::delete($photo->image_path, 'local');
        ImageUploader::delete($photo->thumb_path, 'local');

        $order = $photo->order;
        $photo->delete();
        $order->logActivity('Satu foto produksi dihapus');

        return back()->with('success', 'Foto dihapus.');
    }

    /** Serve any production photo to an authenticated admin. */
    public function file(Request $request, ProductionPhoto $photo, string $kind = 'full')
    {
        $path = $kind === 'thumb' && $photo->thumb_path ? $photo->thumb_path : $photo->image_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}

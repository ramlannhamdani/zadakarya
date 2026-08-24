<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductionPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $order = null;
        $notFound = false;
        $number = trim((string) $request->query('order', ''));

        if ($number !== '') {
            $normalized = strtoupper($number);
            if (! str_starts_with($normalized, 'ZDK-') && ctype_digit($normalized)) {
                $normalized = 'ZDK-'.str_pad($normalized, 4, '0', STR_PAD_LEFT);
            }

            $order = Order::with(['items', 'stages', 'publicPhotos'])
                ->where('order_number', $normalized)
                ->first();

            $notFound = $order === null;
        }

        $showOngoing = setting('show_ongoing', '1') === '1';

        return view('site.tracking', [
            'number' => $number,
            'order' => $order,
            'notFound' => $notFound,
            'ongoing' => $showOngoing
                ? Order::with('items')->where('status', 'active')->latest()->take(10)->get()
                : collect(),
        ]);
    }

    /** Serve a production photo file — public photos only, never internal ones. */
    public function photo(Request $request, ProductionPhoto $photo)
    {
        abort_unless($photo->isPublic(), 404);

        $path = $request->query('thumb') && $photo->thumb_path ? $photo->thumb_path : $photo->image_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

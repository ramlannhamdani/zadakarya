<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;

class GalleryController extends Controller
{
    public function index()
    {
        return view('site.gallery', [
            'items' => GalleryItem::latest()->paginate(30),
        ]);
    }
}

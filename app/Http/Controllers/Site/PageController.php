<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function about()
    {
        return view('site.about');
    }

    public function contact()
    {
        return view('site.contact');
    }
}

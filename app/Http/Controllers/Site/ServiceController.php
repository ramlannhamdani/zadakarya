<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        return view('site.services.index', [
            'services' => Service::published()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Service $service)
    {
        abort_unless($service->is_published, 404);

        return view('site.services.show', [
            'service' => $service,
            'otherServices' => Service::published()
                ->where('id', '!=', $service->id)
                ->orderBy('sort_order')
                ->take(4)
                ->get(),
        ]);
    }
}

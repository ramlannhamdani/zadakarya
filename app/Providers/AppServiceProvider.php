<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Semua URL yang dihasilkan memakai https di production (link, redirect, asset).
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}

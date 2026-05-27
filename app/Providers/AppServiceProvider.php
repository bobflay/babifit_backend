<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Spec returns single objects bare; list endpoints build their own
        // { data, nextCursor } envelope, so global wrapping is disabled.
        JsonResource::withoutWrapping();
    }
}

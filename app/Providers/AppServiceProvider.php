<?php

namespace App\Providers;

use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Observers\HotelBranchObserver;
use App\Observers\HotelOrganizationObserver;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        HotelOrganization::observe(HotelOrganizationObserver::class);
        HotelBranch::observe(HotelBranchObserver::class);
    }
}

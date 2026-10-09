<?php

namespace App\Providers;

use App\Events\CheckinCompleted;
use App\Events\CheckoutCompleted;
use App\Events\ReservationCancelled;
use App\Listeners\HandleCheckinRoomUpdate;
use App\Listeners\HandleCheckoutRoomUpdate;
use App\Listeners\HandleReservationCancellation;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Observers\AuditLogObserver;
use App\Observers\HotelBranchObserver;
use App\Observers\HotelOrganizationObserver;
use Illuminate\Support\Facades\Event;
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
        AuditLogObserver::registerModels();

        Event::listen(ReservationCancelled::class, HandleReservationCancellation::class);
        Event::listen(CheckinCompleted::class, HandleCheckinRoomUpdate::class);
        Event::listen(CheckoutCompleted::class, HandleCheckoutRoomUpdate::class);
    }
}

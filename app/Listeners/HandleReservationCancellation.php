<?php

namespace App\Listeners;

use App\Events\ReservationCancelled;
use App\Services\ReservationService;

class HandleReservationCancellation
{
    public function handle(ReservationCancelled $event): void
    {
        $reservation = $event->reservation;

        // Free the room only if no other active reservation still holds it
        // (never clobbers occupied/maintenance states).
        app(ReservationService::class)->releaseRoomIfReserved(
            $reservation->room_id,
            $reservation->id,
        );
    }
}

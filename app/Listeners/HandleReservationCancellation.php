<?php

namespace App\Listeners;

use App\Events\ReservationCancelled;
use App\Models\Room;

class HandleReservationCancellation
{
    public function handle(ReservationCancelled $event): void
    {
        $reservation = $event->reservation;

        // Free up the room if it was reserved
        if ($reservation->room_id) {
            Room::where('id', $reservation->room_id)
                ->where('status', 'reserved')
                ->update(['status' => 'available']);
        }
    }
}

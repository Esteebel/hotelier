<?php

namespace App\Listeners;

use App\Events\CheckoutCompleted;
use App\Models\Reservation;
use App\Models\Room;

class HandleCheckoutRoomUpdate
{
    public function handle(CheckoutCompleted $event): void
    {
        $checkout = $event->checkout;

        // Update room status to cleaning
        if ($checkout->room_id) {
            Room::where('id', $checkout->room_id)->update(['status' => 'cleaning']);
        }

        // Update reservation status to checked_out
        if ($checkout->reservation_id) {
            Reservation::where('id', $checkout->reservation_id)->update(['status' => 'checked_out']);
        }
    }
}

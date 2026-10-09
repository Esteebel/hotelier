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

        // Idempotent with CheckoutController::store — a vacated room goes to
        // dirty (awaiting housekeeping), never clobbered by other states.
        if ($checkout->room_id) {
            Room::withoutGlobalScope('organization')
                ->where('id', $checkout->room_id)
                ->whereIn('status', ['occupied', 'reserved'])
                ->update(['status' => 'dirty']);
        }

        if ($checkout->reservation_id) {
            Reservation::withoutGlobalScope('branch')
                ->where('id', $checkout->reservation_id)
                ->update(['status' => 'checked_out']);
        }
    }
}

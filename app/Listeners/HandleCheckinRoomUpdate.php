<?php

namespace App\Listeners;

use App\Events\CheckinCompleted;
use App\Models\Reservation;
use App\Models\Room;

class HandleCheckinRoomUpdate
{
    public function handle(CheckinCompleted $event): void
    {
        $checkin = $event->checkin;

        // Idempotent with CheckinController::store — updates are keyed by id
        // and ignore the caller's branch context.
        if ($checkin->room_id) {
            Room::withoutGlobalScope('organization')
                ->where('id', $checkin->room_id)
                ->whereIn('status', ['available', 'reserved', 'dirty', 'cleaning'])
                ->update(['status' => 'occupied']);
        }

        if ($checkin->reservation_id) {
            Reservation::withoutGlobalScope('branch')
                ->where('id', $checkin->reservation_id)
                ->update(['status' => 'checked_in']);
        }
    }
}

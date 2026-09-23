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

        // Update room status to occupied
        if ($checkin->room_id) {
            Room::where('id', $checkin->room_id)->update(['status' => 'occupied']);
        }

        // Update reservation status to checked_in
        if ($checkin->reservation_id) {
            Reservation::where('id', $checkin->reservation_id)->update(['status' => 'checked_in']);
        }
    }
}

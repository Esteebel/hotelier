<?php

namespace App\Listeners;

use App\Events\RoomStatusChanged;

class UpdateRoomAvailability
{
    public function handle(RoomStatusChanged $event): void
    {
        $room = $event->room;

        // Room becomes available when status changes to 'available' or 'maintenance_done'
        if (in_array($event->newStatus, ['available', 'maintenance_done'])) {
            $room->update(['status' => 'available']);
        }
    }
}

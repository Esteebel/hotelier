<?php

namespace App\Services;

use App\Enums\RoomStatus;
use App\Events\CheckinCompleted;
use App\Models\Checkin;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class CheckinService
{
    /**
     * Process a guest check-in.
     */
    public function process(array $data, int $processedBy): Checkin
    {
        return DB::transaction(function () use ($data, $processedBy) {
            $reservation = Reservation::findOrFail($data['reservation_id']);

            if ($reservation->status !== 'confirmed') {
                throw new \RuntimeException('Only confirmed reservations can be checked in.');
            }

            $room = Room::findOrFail($data['room_id']);

            if ($room->status !== RoomStatus::Reserved->value) {
                throw new \RuntimeException('Room is not in reserved status.');
            }

            $checkin = Checkin::create([
                'checkin_number' => Checkin::nextCheckinNumber(),
                'branch_id' => $reservation->branch_id,
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'guest_id' => $reservation->guest_id,
                'processed_by' => $processedBy,
                'checked_in_at' => now(),
                'status' => 'checked_in',
                'notes' => $data['notes'] ?? null,
            ]);

            $reservation->update(['status' => 'checked_in']);
            $room->update(['status' => RoomStatus::Occupied->value]);

            CheckinCompleted::dispatch($checkin, $reservation->branch_id);

            return $checkin;
        });
    }
}

<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Events\ReservationCancelled;
use App\Events\ReservationCreated;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Check room availability for given dates.
     *
     * @return array<int, array{id: int, room_number: string, room_type: string, rate: float}>
     */
    public function checkAvailability(int $branchId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): array
    {
        $branchRoomIds = Room::where('branch_id', $branchId)
            ->where('status', RoomStatus::Available->value)
            ->where('is_active', true)
            ->pluck('id');

        $bookedRoomIds = Reservation::query()
            ->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::CheckedIn->value])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->pluck('room_id');

        return Room::query()
            ->where('branch_id', $branchId)
            ->where('status', RoomStatus::Available->value)
            ->where('is_active', true)
            ->whereIn('id', $branchRoomIds->diff($bookedRoomIds))
            ->with('roomType:id,name,base_price')
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $room->roomType?->name,
                'rate' => (float) ($room->roomType?->base_price ?? 0),
            ])
            ->all();
    }

    /**
     * Check if a room is available for given dates.
     */
    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        $conflict = Reservation::query()
            ->where('room_id', $roomId)
            ->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::CheckedIn->value])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->exists();

        return ! $conflict;
    }

    /**
     * Create a new reservation with availability validation.
     */
    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            if (! $this->isRoomAvailable($data['room_id'], $data['check_in'], $data['check_out'])) {
                throw new \RuntimeException('The selected room is not available for the chosen dates.');
            }

            $room = Room::with('roomType')->findOrFail($data['room_id']);
            $nights = max(1, \Carbon\Carbon::parse($data['check_in'])->diffInDays($data['check_out']));
            $rate = (float) ($room->roomType?->base_price ?? 0);
            $totalAmount = $rate * $nights;

            $data['branch_id'] = $room->branch_id;
            $data['total_amount'] = $totalAmount;
            $data['status'] = ReservationStatus::Confirmed->value;

            $reservation = Reservation::create($data);

            // Mark room as reserved
            $room->update(['status' => RoomStatus::Reserved->value]);

            ReservationCreated::dispatch($reservation, $room->branch_id);

            return $reservation;
        });
    }

    /**
     * Cancel a reservation and release the room.
     */
    public function cancel(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => ReservationStatus::Cancelled->value]);

            // Release a reserved room back to available (never clobbers
            // occupied/maintenance states — HandleReservationCancellation
            // only flips rooms that are still marked reserved).
            ReservationCancelled::dispatch($reservation, $reservation->branch_id);
        });
    }
}

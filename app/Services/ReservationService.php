<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Events\ReservationCancelled;
use App\Events\ReservationCreated;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /** Statuses that hold a room for a date range (block double-booking). */
    private const BLOCKING_STATUSES = [
        ReservationStatus::Confirmed->value,
        ReservationStatus::CheckedIn->value,
        ReservationStatus::Extended->value,
    ];

    /** Physical room states a reservation may transition into "reserved". */
    private const RESERVABLE_ROOM_STATUSES = [
        RoomStatus::Available->value,
        RoomStatus::Dirty->value,
    ];

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

        $bookedRoomIds = $this->blockingRoomIds($checkIn, $checkOut, $excludeReservationId);

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
     * Check if a room is available for given dates (no overlapping active
     * reservation holds the room).
     */
    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        // Scoped to the room itself (never to the request's branch context),
        // so portal and staff flows both see every hold on that room.
        $conflict = Reservation::query()
            ->withoutGlobalScope('branch')
            ->where('room_id', $roomId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->exists();

        return ! $conflict;
    }

    /**
     * Create a new reservation with availability validation, a row lock to
     * serialise concurrent bookings of the same room, and server-side pricing.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \RuntimeException when the room is not available
     */
    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $roomId = (int) $data['room_id'];

            // Row lock serialises concurrent bookings of the same room so two
            // overlapping reservations can never both pass the check.
            $room = Room::with('roomType')
                ->where('id', $roomId)
                ->lockForUpdate()
                ->first();

            if (! $room) {
                throw new \RuntimeException('The selected room does not exist.');
            }

            if (! $this->isRoomAvailable($roomId, $data['check_in'], $data['check_out'])) {
                throw new \RuntimeException('The selected room is not available for the chosen dates.');
            }

            $total = $this->computeTotal($room, $data['check_in'], $data['check_out']);

            $data['branch_id'] = $room->branch_id;
            $data['total_amount'] = $total;
            $data['balance_due'] = $total;
            $data['status'] = ReservationStatus::Confirmed->value;

            $reservation = Reservation::create($data);

            $this->markRoomReserved($room);

            ReservationCreated::dispatch($reservation, $room->branch_id);

            return $reservation;
        });
    }

    /**
     * Update a reservation: re-validates the hold on the room (excluding the
     * reservation itself), recomputes totals server-side, and keeps room
     * statuses coherent when the room or status changes.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \RuntimeException when the target room is not available
     */
    public function update(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data) {
            $newRoomId = isset($data['room_id']) && $data['room_id'] !== null
                ? (int) $data['room_id']
                : $reservation->room_id;
            $oldRoomId = $reservation->room_id;
            $checkIn = $data['check_in'] ?? $reservation->check_in->toDateString();
            $checkOut = $data['check_out'] ?? $reservation->check_out->toDateString();
            $status = $data['status'] ?? $reservation->status->value;
            $roomChanged = $newRoomId !== $reservation->room_id;
            $holdsRoom = in_array($status, self::BLOCKING_STATUSES, true);

            $room = null;
            if ($newRoomId) {
                $room = Room::with('roomType')->where('id', $newRoomId)->lockForUpdate()->first();

                if (! $room) {
                    throw new \RuntimeException('The selected room does not exist.');
                }
            }

            if ($room && $holdsRoom) {
                if (! $this->isRoomAvailable($newRoomId, $checkIn, $checkOut, $reservation->id)) {
                    throw new \RuntimeException('The selected room is not available for the chosen dates.');
                }

                $paid = (float) $reservation->payments()
                    ->where('status', 'completed')
                    ->sum('amount');
                $total = $this->computeTotal($room, $checkIn, $checkOut);

                $data['total_amount'] = $total;
                $data['balance_due'] = max(0, $total - $paid);
            }

            $reservation->update($data);

            if (! $holdsRoom) {
                // Terminal status via edit (cancelled/checked_out/no_show):
                // release the hold, but never steal a room another active
                // reservation still needs.
                $this->releaseRoomIfReserved($newRoomId, $reservation->id);
            }

            if ($roomChanged && $room) {
                $this->releaseRoomIfReserved($oldRoomId, $reservation->id);
            }

            if ($room && $holdsRoom) {
                $this->reserveRoomForStay($room, $status);
            }

            return $reservation->refresh();
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

    /**
     * Free a reserved room when no other active reservation holds it.
     */
    public function releaseRoomIfReserved(?int $roomId, ?int $excludeReservationId = null): void
    {
        if (! $roomId) {
            return;
        }

        $room = Room::find($roomId);

        if (! $room || $room->status->value !== RoomStatus::Reserved->value) {
            return;
        }

        $otherHold = Reservation::query()
            ->withoutGlobalScope('branch')
            ->where('room_id', $roomId)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->exists();

        if (! $otherHold) {
            $room->update(['status' => RoomStatus::Available]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function computeTotal(Room $room, string $checkIn, string $checkOut): float
    {
        $nights = max(1, Carbon::parse($checkIn)->diffInDays($checkOut));
        $rate = (float) ($room->price ?: ($room->roomType?->base_price ?? 0));

        return round($rate * $nights, 2);
    }

    /**
     * @return Collection<int, int|null>
     */
    private function blockingRoomIds(string $checkIn, string $checkOut, ?int $excludeReservationId = null)
    {
        return Reservation::query()
            ->withoutGlobalScope('branch')
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->pluck('room_id');
    }

    private function markRoomReserved(Room $room): void
    {
        if (in_array($room->status->value, self::RESERVABLE_ROOM_STATUSES, true)) {
            $room->update(['status' => RoomStatus::Reserved]);
        }
    }

    private function reserveRoomForStay(Room $room, string $status): void
    {
        $target = match ($status) {
            ReservationStatus::CheckedIn->value,
            ReservationStatus::Extended->value => RoomStatus::Occupied,
            default => RoomStatus::Reserved,
        };

        if ($target === RoomStatus::Reserved) {
            $this->markRoomReserved($room);

            return;
        }

        // Only take over physically empty rooms; never clobber a stay in
        // progress or a room under maintenance.
        if (in_array($room->status->value, self::RESERVABLE_ROOM_STATUSES, true)
            || $room->status->value === RoomStatus::Reserved->value
        ) {
            $room->update(['status' => $target]);
        }
    }
}

<?php

namespace App\Services;

use App\Enums\RoomStatus;
use App\Events\CheckoutCompleted;
use App\Models\Checkout;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Process a guest check-out with balance calculation.
     */
    public function process(array $data, int $processedBy): Checkout
    {
        return DB::transaction(function () use ($data, $processedBy) {
            $reservation = Reservation::with('payments')->findOrFail($data['reservation_id']);

            if ($reservation->status !== 'checked_in') {
                throw new \RuntimeException('Only checked-in reservations can be checked out.');
            }

            $totalPaid = $reservation->payments()
                ->where('status', 'completed')
                ->sum('amount');

            $balanceDue = max(0, $reservation->total_amount - $totalPaid);

            $checkout = Checkout::create([
                'checkout_number' => Checkout::nextCheckoutNumber(),
                'branch_id' => $reservation->branch_id,
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'guest_id' => $reservation->guest_id,
                'processed_by' => $processedBy,
                'checked_out_at' => now(),
                'total_charges' => $reservation->total_amount,
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            $reservation->update(['status' => 'checked_out']);
            Room::where('id', $reservation->room_id)
                ->update(['status' => RoomStatus::Dirty->value]);

            CheckoutCompleted::dispatch($checkout, $reservation->branch_id);

            return $checkout;
        });
    }
}

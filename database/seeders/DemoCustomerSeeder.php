<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCustomerSeeder extends Seeder
{
    /**
     * Seed a guest-portal customer linked to a guest with demo bills.
     */
    public function run(): void
    {
        $guest = Guest::query()
            ->whereHas('invoices')
            ->whereHas('payments')
            ->orderBy('id')
            ->first()
            ?? Guest::query()->orderBy('id')->first();

        $customer = User::firstOrCreate(
            ['email' => 'guest@heritage.ng'],
            [
                'name' => $guest ? $guest->full_name : 'Demo Guest',
                'password' => 'password',
                'user_type' => 'customer',
                'guest_id' => $guest?->id,
            ],
        );

        $customer->assignRole('customer');

        if ($guest && $customer->guest_id !== $guest->id) {
            $customer->update([
                'guest_id' => $guest->id,
                'name' => $guest->full_name,
            ]);
        }

        if (! $guest) {
            return;
        }

        $reservation = $guest->reservations()->latest('id')->first();
        $payment = $guest->payments()
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $seed = [
            [
                'title' => 'Reservation confirmed',
                'message' => $reservation
                    ? 'Your reservation '.$reservation->reservation_number.' has been confirmed. We look forward to hosting you.'
                    : 'Your reservation has been confirmed. We look forward to hosting you.',
                'type' => 'success',
                'status' => 'unread',
            ],
            [
                'title' => 'Payment received',
                'message' => $payment
                    ? 'We received your payment of ₦'.number_format((float) $payment->amount, 2).' (ref '.$payment->payment_number.'). Thank you.'
                    : 'We received your payment. Thank you.',
                'type' => 'success',
                'status' => 'read',
            ],
        ];

        foreach ($seed as $item) {
            Notification::firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'title' => $item['title'],
                ],
                [
                    'notification_number' => Notification::nextNotificationNumber(),
                    'branch_id' => $guest->branch_id,
                    'message' => $item['message'],
                    'type' => $item['type'],
                    'status' => $item['status'],
                    'notifiable_type' => $guest->getMorphClass(),
                    'notifiable_id' => $guest->id,
                ],
            );
        }
    }
}

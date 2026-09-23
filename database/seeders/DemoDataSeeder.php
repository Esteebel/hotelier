<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Staff;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $branch = HotelBranch::where('status', 'active')->first();
        if (! $branch) {
            return;
        }

        // --- Guests ---
        $guestData = [
            ['first_name' => 'Adaeze', 'last_name' => 'Okonkwo', 'email' => 'adaeze.okonkwo@email.com', 'phone' => '+234 812 345 6701', 'nationality' => 'Nigerian', 'id_type' => 'passport', 'id_number' => 'A12345678', 'is_vip' => true],
            ['first_name' => 'Emeka', 'last_name' => 'Adebayo', 'email' => 'emeka.adebayo@email.com', 'phone' => '+234 812 345 6702', 'nationality' => 'Nigerian', 'id_type' => 'drivers_license', 'id_number' => 'DL98765432', 'is_vip' => false],
            ['first_name' => 'Fatima', 'last_name' => 'Abdullahi', 'email' => 'fatima.abdullahi@email.com', 'phone' => '+234 812 345 6703', 'nationality' => 'Nigerian', 'id_type' => 'national_id', 'id_number' => 'NIG112233', 'is_vip' => false],
            ['first_name' => 'Chinedu', 'last_name' => 'Eze', 'email' => 'chinedu.eze@email.com', 'phone' => '+234 812 345 6704', 'nationality' => 'Nigerian', 'id_type' => 'passport', 'id_number' => 'B23456789', 'is_vip' => true],
            ['first_name' => 'Aisha', 'last_name' => 'Mohammed', 'email' => 'aisha.mohammed@email.com', 'phone' => '+234 812 345 6705', 'nationality' => 'Nigerian', 'id_type' => 'national_id', 'id_number' => 'NIG445566', 'is_vip' => false],
            ['first_name' => 'Olumide', 'last_name' => 'Akinwale', 'email' => 'olumide.akinwale@email.com', 'phone' => '+234 812 345 6706', 'nationality' => 'Nigerian', 'id_type' => 'passport', 'id_number' => 'C34567890', 'is_vip' => false],
            ['first_name' => 'Ngozi', 'last_name' => 'Okafor', 'email' => 'ngozi.okafor@email.com', 'phone' => '+234 812 345 6707', 'nationality' => 'Nigerian', 'id_type' => 'drivers_license', 'id_number' => 'DL55667788', 'is_vip' => false],
            ['first_name' => 'Ibrahim', 'last_name' => 'Bello', 'email' => 'ibrahim.bello@email.com', 'phone' => '+234 812 345 6708', 'nationality' => 'Nigerian', 'id_type' => 'national_id', 'id_number' => 'NIG778899', 'is_vip' => true],
            ['first_name' => 'Chidinma', 'last_name' => 'Nwosu', 'email' => 'chidinma.nwosu@email.com', 'phone' => '+234 812 345 6709', 'nationality' => 'Nigerian', 'id_type' => 'passport', 'id_number' => 'D45678901', 'is_vip' => false],
            ['first_name' => 'Tunde', 'last_name' => 'Bankole', 'email' => 'tunde.bankole@email.com', 'phone' => '+234 812 345 6710', 'nationality' => 'Nigerian', 'id_type' => 'national_id', 'id_number' => 'NIG001122', 'is_vip' => false],
        ];

        $guestModels = collect($guestData)->map(function ($data) use ($branch) {
            $data['branch_id'] = $branch->id;
            return Guest::create($data);
        });

        // --- Rooms (mark some as occupied/reserved) ---
        $rooms = Room::where('branch_id', $branch->id)->get();
        $availableRooms = $rooms->where('status', RoomStatus::Available->value);

        // --- Reservations ---
        $today = Carbon::today();
        $reservations = collect();
        $statuses = [ReservationStatus::Confirmed, ReservationStatus::CheckedIn, ReservationStatus::CheckedOut, ReservationStatus::Cancelled];

        for ($i = 0; $i < min(10, $availableRooms->count()); $i++) {
            $guest = $guestModels[$i % $guestModels->count()];
            $room = $availableRooms[$i];
            $checkIn = $today->copy()->addDays(rand(-5, 2));
            $checkOut = $checkIn->copy()->addDays(rand(1, 5));
            $nights = $checkIn->diffInDays($checkOut);
            $rate = (float) ($room->roomType->base_price ?? 35000);
            $totalAmount = $rate * $nights;

            $status = $statuses[$i % count($statuses)];

            $reservation = Reservation::create([
                'branch_id' => $branch->id,
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'reservation_number' => $branch->code.'-RSV-'.str_pad((string) ($i + 1), 7, '0', STR_PAD_LEFT),
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'adults' => rand(1, 2),
                'children' => rand(0, 2),
                'rate_plan' => 'standard',
                'total_amount' => $totalAmount,
                'status' => $status,
            ]);

            $reservations->push($reservation);

            // Update room status based on reservation status
            if ($status === ReservationStatus::CheckedIn) {
                $room->update(['status' => RoomStatus::Occupied->value]);
            } elseif ($status === ReservationStatus::Confirmed) {
                $room->update(['status' => RoomStatus::Reserved->value]);
            }
        }

        // --- Payments ---
        $paymentCount = 0;
        foreach ($reservations as $reservation) {
            if (in_array($reservation->status, [ReservationStatus::CheckedIn, ReservationStatus::CheckedOut])) {
                $paidAmount = $reservation->total_amount * (rand(50, 100) / 100);
                Payment::create([
                    'branch_id' => $branch->id,
                    'guest_id' => $reservation->guest_id,
                    'reservation_id' => $reservation->id,
                    'payment_number' => $branch->code.'-PAY-'.str_pad((string) (++$paymentCount), 7, '0', STR_PAD_LEFT),
                    'method' => collect(PaymentMethod::cases())->random(),
                    'amount' => round($paidAmount, 2),
                    'paid_on' => $reservation->check_in,
                    'status' => PaymentStatus::Completed,
                ]);
            }
        }

        // --- Invoices ---
        $invoiceCount = 0;
        foreach ($reservations->filter(fn ($r) => in_array($r->status, [ReservationStatus::CheckedIn, ReservationStatus::CheckedOut])) as $reservation) {
            $subtotal = $reservation->total_amount;
            $tax = round($subtotal * 0.075, 2);
            $total = $subtotal + $tax;

            Invoice::create([
                'branch_id' => $branch->id,
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'invoice_number' => $branch->code.'-INV-'.str_pad((string) (++$invoiceCount), 7, '0', STR_PAD_LEFT),
                'issued_on' => $reservation->check_in,
                'due_on' => Carbon::parse($reservation->check_in)->addDays(30)->toDateString(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => 0,
                'total' => $total,
                'amount_paid' => $reservation->total_amount * 0.8,
                'status' => 'partially_paid',
            ]);
        }

        // --- Expenses ---
        $expenseCategories = ['Utilities', 'Maintenance', 'Supplies', 'Staff Welfare', 'Marketing', 'Cleaning'];
        for ($i = 0; $i < 8; $i++) {
            Expense::create([
                'branch_id' => $branch->id,
                'expense_number' => $branch->code.'-EXP-'.str_pad((string) ($i + 1), 7, '0', STR_PAD_LEFT),
                'description' => $expenseCategories[array_rand($expenseCategories)].' expense',
                'amount' => rand(5000, 150000),
                'category' => $expenseCategories[array_rand($expenseCategories)],
                'expense_date' => $today->copy()->subDays(rand(0, 14))->toDateString(),
                'reference' => $branch->code.'-REF-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'status' => 'completed',
                'notes' => null,
            ]);
        }

        // --- Staff ---
        $staffData = [
            ['first_name' => 'Grace', 'last_name' => 'Adeyemi', 'position' => 'Front Desk Officer', 'department' => 'Reception', 'email' => 'grace.adeyemi@heritage.ng', 'phone' => '+234 813 000 1001', 'status' => 'active'],
            ['first_name' => 'Yusuf', 'last_name' => 'Lawal', 'position' => 'Housekeeping Supervisor', 'department' => 'Housekeeping', 'email' => 'yusuf.lawal@heritage.ng', 'phone' => '+234 813 000 1002', 'status' => 'active'],
            ['first_name' => 'Blessing', 'last_name' => 'Okoro', 'position' => 'Accountant', 'department' => 'Finance', 'email' => 'blessing.okoro@heritage.ng', 'phone' => '+234 813 000 1003', 'status' => 'active'],
            ['first_name' => 'Musa', 'last_name' => 'Garba', 'position' => 'Maintenance Technician', 'department' => 'Maintenance', 'email' => 'musa.garba@heritage.ng', 'phone' => '+234 813 000 1004', 'status' => 'active'],
            ['first_name' => 'Funke', 'last_name' => 'Adeleke', 'position' => 'Guest Relations Manager', 'department' => 'Front Office', 'email' => 'funke.adeleke@heritage.ng', 'phone' => '+234 813 000 1005', 'status' => 'active'],
        ];

        $staffCount = 0;
        foreach ($staffData as $data) {
            Staff::create(array_merge($data, [
                'branch_id' => $branch->id,
                'staff_number' => $branch->code.'-STF-'.str_pad((string) (++$staffCount), 7, '0', STR_PAD_LEFT),
            ]));
        }
    }
}

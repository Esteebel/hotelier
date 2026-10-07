<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SecondaryOrganizationSeeder extends Seeder
{
    /**
     * A second, fully isolated organization with its own hotel admin —
     * used to demonstrate super admin vs hotel admin visibility.
     */
    public function run(): void
    {
        $organization = HotelOrganization::firstOrCreate(
            ['code' => 'SRN'],
            [
                'name' => 'Sunrise Suites',
                'description' => 'Boutique suites on the island.',
                'address' => '5 Ahmadu Bello Way',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'country' => 'Nigeria',
                'phone' => '+234 802 000 2222',
                'email' => 'hello@sunrise.ng',
                'currency' => 'NGN',
                'star_rating' => 5,
                'status' => 'active',
            ],
        );

        $branch = HotelBranch::firstOrCreate(
            ['code' => 'SRJ'],
            [
                'organization_id' => $organization->id,
                'name' => 'Sunrise Ikeja',
                'description' => 'Sunrise Suites flagship branch in Ikeja.',
                'address' => '12 Allen Avenue',
                'city' => 'Ikeja',
                'state' => 'Lagos State',
                'country' => 'Nigeria',
                'phone' => '+234 802 000 2223',
                'email' => 'ikeja@sunrise.ng',
                'currency' => 'NGN',
                'star_rating' => 5,
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
                'status' => 'active',
                'is_default' => false,
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'sunrise.admin@heritage.ng'],
            [
                'name' => 'Sunrise Admin',
                'password' => 'password',
                'user_type' => 'staff',
                'organization_id' => $organization->id,
            ],
        );

        $admin->assignRole('hotel-admin');
        $admin->branches()->syncWithoutDetaching([$branch->id]);

        // Demo inventory + one reservation, so the dashboard visibly differs
        // between "All organizations", Heritage and Sunrise.
        if (! Room::where('branch_id', $branch->id)->exists()) {
            $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);

            $room = Room::factory()->create([
                'branch_id' => $branch->id,
                'room_type_id' => $roomType->id,
                'room_number' => 'SRJ-101',
                'capacity' => 2,
            ]);

            Room::factory()->create([
                'branch_id' => $branch->id,
                'room_type_id' => $roomType->id,
                'room_number' => 'SRJ-102',
                'capacity' => 2,
            ]);

            $guest = Guest::create([
                'branch_id' => $branch->id,
                'first_name' => 'Sunrise',
                'last_name' => 'Guest',
                'email' => 'sunrise.guest@example.com',
                'status' => 'active',
            ]);

            Reservation::create([
                'branch_id' => $branch->id,
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'reservation_number' => 'SRJ-RSV-0000001',
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'adults' => 2,
                'children' => 0,
                'rate_plan' => 'standard',
                'total_amount' => 120000,
                'status' => 'confirmed',
            ]);
        }
    }
}

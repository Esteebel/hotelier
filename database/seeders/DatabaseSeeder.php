<?php

namespace Database\Seeders;

use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Permissions + Roles
        $this->call(PermissionSeeder::class);

        // 2. Demo users
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@heriatge.ng'],
            ['name' => 'Super Admin', 'password' => 'password'],
        );
        $superAdmin->assignRole('super-admin');

        $hotelAdmin = User::firstOrCreate(
            ['email' => 'hotel.admin@heritage.ng'],
            ['name' => 'Hotel Admin', 'password' => 'password'],
        );
        $hotelAdmin->assignRole('hotel-admin');

        $manager = User::firstOrCreate(
            ['email' => 'manager@heritage.ng'],
            ['name' => 'Branch Manager', 'password' => 'password'],
        );
        $manager->assignRole('manager');

        $receptionist = User::firstOrCreate(
            ['email' => 'reception@heritage.ng'],
            ['name' => 'Receptionist', 'password' => 'password'],
        );
        $receptionist->assignRole('receptionist');

        // Keep original test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );
        $testUser->assignRole('super-admin');

        // 3. Organizations + Branches + default branch
        $this->call(OrganizationSeeder::class);

        // 4. Room types + rooms
        $this->call(RoomSeeder::class);

        // 5. Link all users to the first two branches
        $branches = HotelBranch::where('status', 'active')->take(2)->get();

        collect([$superAdmin, $hotelAdmin, $manager, $receptionist, $testUser])->each(
            fn (User $user) => $user->branches()->syncWithoutDetaching($branches->pluck('id')),
        );

        // Tenant ownership: every non-super-admin belongs to the organization
        // of their first branch. Super admins stay platform-level (NULL).
        collect([$hotelAdmin, $manager, $receptionist])->each(function (User $user): void {
            $organizationId = $user->branches()->first()?->organization_id;

            if ($organizationId) {
                $user->update(['organization_id' => $organizationId]);
            }
        });

        // // 6. Demo data (guests, reservations, payments, invoices, expenses, staff)
        // $this->call(DemoDataSeeder::class);

        // // 7. Restaurant menu items
        // $this->call(MenuSeeder::class);

        // // 8. Demo guest-portal customer
        // $this->call(DemoCustomerSeeder::class);

        // // 9. Second organization + its own hotel admin (tenant isolation demo)
        // $this->call(SecondaryOrganizationSeeder::class);
    }
}
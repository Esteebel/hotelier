<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Create every permission and role, then assign permissions to each role.
     */
    public function run(): void
    {
        // 1. Create all permissions
        $allPermissions = collect(PermissionName::cases())->map(
            fn (PermissionName $perm) => Permission::firstOrCreate(['name' => $perm->value, 'guard_name' => 'web']),
        );

        // 2. Create roles
        $roles = collect([
            'super-admin' => 'Full system access.',
            'hotel-admin' => 'Manage hotel operations and staff.',
            'manager' => 'Operational and management modules.',
            'receptionist' => 'Reservations, guests, check-in, check-out, payments, invoices.',
            'accountant' => 'Financial modules: payments, invoices, expenses, reports.',
            'housekeeper' => 'Housekeeping tasks and room status.',
            'maintenance-staff' => 'Maintenance requests and tracking.',
            'staff' => 'Limited general access.',
        ])->map(fn (string $desc, string $name) => Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        // 3. Assign permissions to each role
        $roles['super-admin']->syncPermissions($allPermissions);

        $roles['hotel-admin']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => ! str_starts_with($p->name, 'manage-roles')),
        );

        $roles['manager']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => in_array($p->name, [
                PermissionName::ViewDashboard->value,
                PermissionName::ViewBranches->value,
                PermissionName::ViewStaff->value,
                PermissionName::ManageStaff->value,
                PermissionName::ViewGuests->value,
                PermissionName::ManageGuests->value,
                PermissionName::ViewRooms->value,
                PermissionName::ViewRoomTypes->value,
                PermissionName::ViewReservations->value,
                PermissionName::CreateReservations->value,
                PermissionName::ManageReservations->value,
                PermissionName::CancelReservations->value,
                PermissionName::ManageCheckins->value,
                PermissionName::ManageCheckouts->value,
                PermissionName::ManageRoomTransfers->value,
                PermissionName::ViewHousekeeping->value,
                PermissionName::ManageHousekeeping->value,
                PermissionName::ViewMaintenance->value,
                PermissionName::ManageMaintenance->value,
                PermissionName::ViewServices->value,
                PermissionName::ManageServices->value,
                PermissionName::ManageRestaurantOrders->value,
                PermissionName::ViewPayments->value,
                PermissionName::ManagePayments->value,
                PermissionName::ViewInvoices->value,
                PermissionName::ManageInvoices->value,
                PermissionName::ViewExpenses->value,
                PermissionName::ViewReports->value,
                PermissionName::ViewNotifications->value,
            ])),
        );

        $roles['receptionist']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => in_array($p->name, [
                PermissionName::ViewDashboard->value,
                PermissionName::ViewBranches->value,
                PermissionName::ViewGuests->value,
                PermissionName::ManageGuests->value,
                PermissionName::ViewRooms->value,
                PermissionName::ViewReservations->value,
                PermissionName::CreateReservations->value,
                PermissionName::ManageReservations->value,
                PermissionName::CancelReservations->value,
                PermissionName::ManageCheckins->value,
                PermissionName::ManageCheckouts->value,
                PermissionName::ManageRoomTransfers->value,
                PermissionName::ViewPayments->value,
                PermissionName::ManagePayments->value,
                PermissionName::ViewInvoices->value,
                PermissionName::ManageInvoices->value,
                PermissionName::ViewServices->value,
                PermissionName::ViewNotifications->value,
            ])),
        );

        $roles['accountant']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => in_array($p->name, [
                PermissionName::ViewDashboard->value,
                PermissionName::ViewBranches->value,
                PermissionName::ViewPayments->value,
                PermissionName::ManagePayments->value,
                PermissionName::ViewInvoices->value,
                PermissionName::ManageInvoices->value,
                PermissionName::ViewExpenses->value,
                PermissionName::ManageExpenses->value,
                PermissionName::ViewReports->value,
                PermissionName::ViewNotifications->value,
            ])),
        );

        $roles['housekeeper']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => in_array($p->name, [
                PermissionName::ViewDashboard->value,
                PermissionName::ViewHousekeeping->value,
                PermissionName::ManageHousekeeping->value,
                PermissionName::ViewRooms->value,
                PermissionName::ViewNotifications->value,
            ])),
        );

        $roles['maintenance-staff']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => in_array($p->name, [
                PermissionName::ViewDashboard->value,
                PermissionName::ViewMaintenance->value,
                PermissionName::ManageMaintenance->value,
                PermissionName::ViewRooms->value,
                PermissionName::ViewNotifications->value,
            ])),
        );

        $roles['staff']->syncPermissions(
            $allPermissions->filter(fn (Permission $p) => in_array($p->name, [
                PermissionName::ViewDashboard->value,
                PermissionName::ViewNotifications->value,
            ])),
        );
    }
}
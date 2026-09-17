<?php

namespace App\Enums;

enum PermissionName: string
{
    // Dashboard
    case ViewDashboard = 'view-dashboard';

    // Hotel / Organizations
    case ViewHotels = 'view-hotels';
    case ManageHotels = 'manage-hotels';

    // Hotel Branches
    case ViewBranches = 'view-branches';
    case ManageBranches = 'manage-branches';

    // Users
    case ViewUsers = 'view-users';
    case ManageUsers = 'manage-users';

    // Roles & Permissions
    case ManageRoles = 'manage-roles';

    // Staff
    case ViewStaff = 'view-staff';
    case ManageStaff = 'manage-staff';

    // Guests
    case ViewGuests = 'view-guests';
    case ManageGuests = 'manage-guests';

    // Room Types
    case ViewRoomTypes = 'view-room-types';
    case ManageRoomTypes = 'manage-room-types';

    // Rooms
    case ViewRooms = 'view-rooms';
    case ManageRooms = 'manage-rooms';

    // Reservations
    case ViewReservations = 'view-reservations';
    case CreateReservations = 'create-reservations';
    case ManageReservations = 'manage-reservations';
    case CancelReservations = 'cancel-reservations';

    // Check-In / Check-Out
    case ManageCheckins = 'manage-checkins';
    case ManageCheckouts = 'manage-checkouts';

    // Room Transfers
    case ManageRoomTransfers = 'manage-room-transfers';

    // Housekeeping
    case ViewHousekeeping = 'view-housekeeping';
    case ManageHousekeeping = 'manage-housekeeping';

    // Maintenance
    case ViewMaintenance = 'view-maintenance';
    case ManageMaintenance = 'manage-maintenance';

    // Services
    case ViewServices = 'view-services';
    case ManageServices = 'manage-services';

    // Restaurant / Food Orders
    case ManageRestaurantOrders = 'manage-restaurant-orders';

    // Payments
    case ViewPayments = 'view-payments';
    case ManagePayments = 'manage-payments';

    // Invoices
    case ViewInvoices = 'view-invoices';
    case ManageInvoices = 'manage-invoices';

    // Expenses
    case ViewExpenses = 'view-expenses';
    case ManageExpenses = 'manage-expenses';

    // Reports
    case ViewReports = 'view-reports';

    // Notifications
    case ViewNotifications = 'view-notifications';
    case ManageNotifications = 'manage-notifications';

    // Audit Logs
    case ViewAuditLogs = 'view-audit-logs';

    // Settings
    case ManageSettings = 'manage-settings';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(
            fn (self $perm) => str($perm->value)->replace('-', ' ')->title()->toString(),
            self::cases(),
        );
    }

    /**
     * Group permissions by module for easier UI rendering.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        return [
            'Dashboard' => [self::ViewDashboard],
            'Hotels & Branches' => [
                self::ViewHotels, self::ManageHotels,
                self::ViewBranches, self::ManageBranches,
            ],
            'Users & Roles' => [
                self::ViewUsers, self::ManageUsers, self::ManageRoles,
            ],
            'Staff' => [self::ViewStaff, self::ManageStaff],
            'Guests' => [self::ViewGuests, self::ManageGuests],
            'Rooms' => [
                self::ViewRoomTypes, self::ManageRoomTypes,
                self::ViewRooms, self::ManageRooms,
            ],
            'Reservations' => [
                self::ViewReservations, self::CreateReservations,
                self::ManageReservations, self::CancelReservations,
            ],
            'Operations' => [
                self::ManageCheckins, self::ManageCheckouts,
                self::ManageRoomTransfers,
            ],
            'Housekeeping & Maintenance' => [
                self::ViewHousekeeping, self::ManageHousekeeping,
                self::ViewMaintenance, self::ManageMaintenance,
            ],
            'Services & Dining' => [
                self::ViewServices, self::ManageServices,
                self::ManageRestaurantOrders,
            ],
            'Finance' => [
                self::ViewPayments, self::ManagePayments,
                self::ViewInvoices, self::ManageInvoices,
                self::ViewExpenses, self::ManageExpenses,
            ],
            'Reports & System' => [
                self::ViewReports,
                self::ViewNotifications, self::ManageNotifications,
                self::ViewAuditLogs, self::ManageSettings,
            ],
        ];
    }
}
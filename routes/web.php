<?php

use App\Http\Controllers\HotelBranchController;
use App\Http\Controllers\HotelOrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\HousekeepingTaskController;
use App\Http\Controllers\MaintenanceTaskController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\FoodOrderController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\LaundryOrderController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\RoomTransferController;
use App\Http\Controllers\ReportController;

use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified', 'staff.area'])
    ->name('dashboard');

Route::middleware(['auth', 'staff.area'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Branch switching
    Route::post('/active-branch', [HotelBranchController::class, 'switch'])->name('branches.switch');
    Route::post('/active-organization', [HotelOrganizationController::class, 'switch'])->name('organizations.switch');

    // Hotel Organizations
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', [HotelOrganizationController::class, 'index'])->name('index');
        Route::get('/create', [HotelOrganizationController::class, 'create'])->name('create');
        Route::post('/', [HotelOrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}/edit', [HotelOrganizationController::class, 'edit'])->name('edit');
        Route::put('/{organization}', [HotelOrganizationController::class, 'update'])->name('update');
        Route::delete('/{organization}', [HotelOrganizationController::class, 'destroy'])->name('destroy');
    });

    // Hotel Branches
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [HotelBranchController::class, 'index'])->name('index');
        Route::get('/create', [HotelBranchController::class, 'create'])->name('create');
        Route::post('/', [HotelBranchController::class, 'store'])->name('store');
        Route::get('/{branch}/edit', [HotelBranchController::class, 'edit'])->name('edit');
        Route::put('/{branch}', [HotelBranchController::class, 'update'])->name('update');
        Route::delete('/{branch}', [HotelBranchController::class, 'destroy'])->name('destroy');
    });

    // Room Types
    Route::prefix('room-types')->name('room-types.')->group(function () {
        Route::get('/', [RoomTypeController::class, 'index'])->name('index');
        Route::get('/create', [RoomTypeController::class, 'create'])->name('create');
        Route::post('/', [RoomTypeController::class, 'store'])->name('store');
        Route::get('/{roomType}/edit', [RoomTypeController::class, 'edit'])->name('edit');
        Route::put('/{roomType}', [RoomTypeController::class, 'update'])->name('update');
        Route::delete('/{roomType}', [RoomTypeController::class, 'destroy'])->name('destroy');
    });

    // Rooms
    Route::prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('index');
        Route::get('/create', [RoomController::class, 'create'])->name('create');
        Route::post('/', [RoomController::class, 'store'])->name('store');
        Route::get('/{room}/edit', [RoomController::class, 'edit'])->name('edit');
        Route::put('/{room}', [RoomController::class, 'update'])->name('update');
        Route::delete('/{room}', [RoomController::class, 'destroy'])->name('destroy');
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Roles & Permissions
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
    });

    // Guests
    Route::prefix('guests')->name('guests.')->group(function () {
        Route::get('/', [GuestController::class, 'index'])->name('index');
        Route::get('/create', [GuestController::class, 'create'])->name('create');
        Route::post('/', [GuestController::class, 'store'])->name('store');
        Route::get('/{guest}/edit', [GuestController::class, 'edit'])->name('edit');
        Route::put('/{guest}', [GuestController::class, 'update'])->name('update');
        Route::delete('/{guest}', [GuestController::class, 'destroy'])->name('destroy');
    });

    // Reservations
    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/create', [ReservationController::class, 'create'])->name('create');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}/edit', [ReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [ReservationController::class, 'update'])->name('update');
        Route::delete('/{reservation}', [ReservationController::class, 'destroy'])->name('destroy');
    });

    // Payments
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/create', [PaymentController::class, 'create'])->name('create');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
        Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
    });

    // Room Transfers
    Route::prefix('room-transfers')->name('room-transfers.')->group(function () {
        Route::get('/', [RoomTransferController::class, 'index'])->name('index');
        Route::get('/create', [RoomTransferController::class, 'create'])->name('create');
        Route::post('/', [RoomTransferController::class, 'store'])->name('store');
        Route::get('/{roomTransfer}/edit', [RoomTransferController::class, 'edit'])->name('edit');
        Route::put('/{roomTransfer}', [RoomTransferController::class, 'update'])->name('update');
        Route::delete('/{roomTransfer}', [RoomTransferController::class, 'destroy'])->name('destroy');
        Route::post('/{roomTransfer}/complete', [RoomTransferController::class, 'complete'])->name('complete');
    });

    // Room Transfers
    // Housekeeping
    Route::prefix('housekeeping')->name('housekeeping.')->group(function () {
        Route::get('/', [HousekeepingTaskController::class, 'index'])->name('index');
        Route::get('/create', [HousekeepingTaskController::class, 'create'])->name('create');
        Route::post('/', [HousekeepingTaskController::class, 'store'])->name('store');
        Route::get('/{housekeepingTask}/edit', [HousekeepingTaskController::class, 'edit'])->name('edit');
        Route::put('/{housekeepingTask}', [HousekeepingTaskController::class, 'update'])->name('update');
        Route::delete('/{housekeepingTask}', [HousekeepingTaskController::class, 'destroy'])->name('destroy');
        Route::post('/{housekeepingTask}/complete', [HousekeepingTaskController::class, 'complete'])->name('complete');
    });

    // Maintenance
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [MaintenanceTaskController::class, 'index'])->name('index');
        Route::get('/create', [MaintenanceTaskController::class, 'create'])->name('create');
        Route::post('/', [MaintenanceTaskController::class, 'store'])->name('store');
        Route::get('/{maintenanceTask}/edit', [MaintenanceTaskController::class, 'edit'])->name('edit');
        Route::put('/{maintenanceTask}', [MaintenanceTaskController::class, 'update'])->name('update');
        Route::delete('/{maintenanceTask}', [MaintenanceTaskController::class, 'destroy'])->name('destroy');
        Route::post('/{maintenanceTask}/complete', [MaintenanceTaskController::class, 'complete'])->name('complete');
    });

    // Services
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceRequestController::class, 'index'])->name('index');
        Route::get('/create', [ServiceRequestController::class, 'create'])->name('create');
        Route::post('/', [ServiceRequestController::class, 'store'])->name('store');
        Route::get('/{serviceRequest}/edit', [ServiceRequestController::class, 'edit'])->name('edit');
        Route::put('/{serviceRequest}', [ServiceRequestController::class, 'update'])->name('update');
        Route::delete('/{serviceRequest}', [ServiceRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{serviceRequest}/complete', [ServiceRequestController::class, 'complete'])->name('complete');
    });

    // Restaurant Menu
    Route::prefix('restaurant/menu')->name('menu.')->group(function () {
        Route::get('/', [MenuItemController::class, 'index'])->name('index');
        Route::get('/create', [MenuItemController::class, 'create'])->name('create');
        Route::post('/', [MenuItemController::class, 'store'])->name('store');
        Route::get('/{menuItem}/edit', [MenuItemController::class, 'edit'])->name('edit');
        Route::put('/{menuItem}', [MenuItemController::class, 'update'])->name('update');
        Route::delete('/{menuItem}', [MenuItemController::class, 'destroy'])->name('destroy');
    });

    // Restaurant
    Route::prefix('restaurant')->name('restaurant.')->group(function () {
        Route::get('/', [FoodOrderController::class, 'index'])->name('index');
        Route::get('/create', [FoodOrderController::class, 'create'])->name('create');
        Route::post('/', [FoodOrderController::class, 'store'])->name('store');
        Route::get('/{foodOrder}/edit', [FoodOrderController::class, 'edit'])->name('edit');
        Route::put('/{foodOrder}', [FoodOrderController::class, 'update'])->name('update');
        Route::delete('/{foodOrder}', [FoodOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{foodOrder}/complete', [FoodOrderController::class, 'complete'])->name('complete');
    });

    // Laundry
    Route::prefix('laundry')->name('laundry.')->group(function () {
        Route::get('/', [LaundryOrderController::class, 'index'])->name('index');
        Route::get('/create', [LaundryOrderController::class, 'create'])->name('create');
        Route::post('/', [LaundryOrderController::class, 'store'])->name('store');
        Route::get('/{laundryOrder}/edit', [LaundryOrderController::class, 'edit'])->name('edit');
        Route::put('/{laundryOrder}', [LaundryOrderController::class, 'update'])->name('update');
        Route::delete('/{laundryOrder}', [LaundryOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{laundryOrder}/complete', [LaundryOrderController::class, 'complete'])->name('complete');
    });

    // Check-ins
    Route::prefix('checkins')->name('checkins.')->group(function () {
        Route::get('/', [CheckinController::class, 'index'])->name('index');
        Route::get('/create', [CheckinController::class, 'create'])->name('create');
        Route::post('/', [CheckinController::class, 'store'])->name('store');
        Route::get('/{checkin}/edit', [CheckinController::class, 'edit'])->name('edit');
        Route::put('/{checkin}', [CheckinController::class, 'update'])->name('update');
        Route::delete('/{checkin}', [CheckinController::class, 'destroy'])->name('destroy');
        Route::post('/{checkin}/complete', [CheckinController::class, 'complete'])->name('complete');
    });

    // Check-outs
    Route::prefix('checkouts')->name('checkouts.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::get('/create', [CheckoutController::class, 'create'])->name('create');
        Route::post('/', [CheckoutController::class, 'store'])->name('store');
        Route::get('/{checkout}/edit', [CheckoutController::class, 'edit'])->name('edit');
        Route::put('/{checkout}', [CheckoutController::class, 'update'])->name('update');
        Route::delete('/{checkout}', [CheckoutController::class, 'destroy'])->name('destroy');
        Route::post('/{checkout}/complete', [CheckoutController::class, 'complete'])->name('complete');
    });

    // Expenses
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/{expense}/complete', [ExpenseController::class, 'complete'])->name('complete');
    });

    // Staff
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
        Route::post('/{staff}/complete', [StaffController::class, 'complete'])->name('complete');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/create', [NotificationController::class, 'create'])->name('create');
        Route::post('/', [NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
        Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/{notification}/complete', [NotificationController::class, 'complete'])->name('complete');
    });

    // Audit Logs (view-only)
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });
});

// Customer portal
Route::middleware(['auth', 'customer.area'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('/menu', [PortalController::class, 'menu'])->name('menu');
    Route::post('/menu/orders', [PortalController::class, 'storeOrder'])->name('menu.orders.store');
    Route::get('/orders', [PortalController::class, 'orders'])->name('orders');
    Route::get('/rooms', [PortalController::class, 'rooms'])->name('rooms');
    Route::get('/reservations', [PortalController::class, 'reservations'])->name('reservations');
    Route::post('/reservations', [PortalController::class, 'storeReservation'])->name('reservations.store');
    Route::post('/reservations/{reservation}/cancel', [PortalController::class, 'cancelReservation'])->name('reservations.cancel');
    Route::get('/bills', [PortalController::class, 'bills'])->name('bills');
    Route::get('/profile', [PortalController::class, 'profile'])->name('profile');
    Route::post('/notifications/{notification}/read', [PortalController::class, 'readNotification'])->name('notifications.read');
    Route::post('/notifications/read-all', [PortalController::class, 'readAllNotifications'])->name('notifications.readAll');
});

require __DIR__.'/auth.php';

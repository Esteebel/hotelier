<?php

use App\Http\Controllers\HotelBranchController;
use App\Http\Controllers\HotelOrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoiceController;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Branch switching
    Route::post('/active-branch', [HotelBranchController::class, 'switch'])->name('branches.switch');

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

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });
});

require __DIR__.'/auth.php';

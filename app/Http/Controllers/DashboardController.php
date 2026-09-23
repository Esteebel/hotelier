<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\ContextualBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);
        $today = Carbon::today();

        $branchScope = fn ($query) => $query->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId));

        // Room statistics
        $totalRooms = $branchScope(Room::query())->count();
        $occupiedRooms = $branchScope(Room::query())->where('status', RoomStatus::Occupied->value)->count();
        $reservedRooms = $branchScope(Room::query())->where('status', RoomStatus::Reserved->value)->count();
        $availableRooms = $branchScope(Room::query())->where('status', RoomStatus::Available->value)->count();
        $dirtyRooms = $branchScope(Room::query())->where('status', RoomStatus::Dirty->value)->count();
        $maintenanceRooms = $branchScope(Room::query())->where('status', RoomStatus::Maintenance->value)->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Today's arrivals and departures
        $todayArrivals = $branchScope(Reservation::query())
            ->whereDate('check_in', $today)
            ->where('status', 'confirmed')
            ->count();

        $todayDepartures = $branchScope(Reservation::query())
            ->whereDate('check_out', $today)
            ->where('status', 'checked_in')
            ->count();

        // Revenue
        $todayRevenue = $branchScope(Payment::query())
            ->where('status', 'completed')
            ->whereDate('paid_on', $today)
            ->sum('amount');

        $monthRevenue = $branchScope(Payment::query())
            ->where('status', 'completed')
            ->whereDate('paid_on', '>=', $today->copy()->startOfMonth())
            ->sum('amount');

        $pendingPayments = $branchScope(Payment::query())
            ->where('status', 'pending')
            ->sum('amount');

        $outstandingInvoices = $branchScope(Invoice::query())
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->sum(DB::raw('total - amount_paid'));

        // Reservation counts
        $confirmedReservations = $branchScope(Reservation::query())
            ->where('status', 'confirmed')
            ->count();

        $activeGuests = $branchScope(Reservation::query())
            ->where('status', 'checked_in')
            ->count();

        // Recent reservations (last 5)
        $recentReservations = $branchScope(Reservation::query())
            ->with(['guest:id,first_name,last_name', 'room:id,room_number'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Reservation $r) => [
                'id' => $r->id,
                'code' => $r->reservation_number,
                'guest' => $r->guest?->full_name,
                'room' => $r->room?->room_number,
                'check_in' => $r->check_in->toDateString(),
                'check_out' => $r->check_out->toDateString(),
                'status' => $r->status->value,
                'status_label' => $r->status_label,
                'badge_class' => $r->status->badgeClass(),
            ]);

        // Room type breakdown
        $roomTypeBreakdown = Room::query()
            ->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->select('room_type_id', DB::raw('count(*) as total'), DB::raw("sum(case when status = 'occupied' then 1 else 0 end) as occupied"))
            ->groupBy('room_type_id')
            ->with('roomType:id,name')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->roomType?->name ?? 'Unknown',
                'total' => $item->total,
                'occupied' => $item->occupied,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'reserved_rooms' => $reservedRooms,
                'available_rooms' => $availableRooms,
                'dirty_rooms' => $dirtyRooms,
                'maintenance_rooms' => $maintenanceRooms,
                'occupancy_rate' => $occupancyRate,
                'today_arrivals' => $todayArrivals,
                'today_departures' => $todayDepartures,
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'pending_payments' => $pendingPayments,
                'outstanding_invoices' => $outstandingInvoices,
                'confirmed_reservations' => $confirmedReservations,
                'active_guests' => $activeGuests,
            ],
            'recent_reservations' => $recentReservations,
            'room_type_breakdown' => $roomTypeBreakdown,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }
}

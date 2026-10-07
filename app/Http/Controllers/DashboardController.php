<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\ContextualBranch;
use App\Support\ContextualOrganization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Scope rules:
        // - an explicitly selected branch (session or ?branch_id=) narrows the
        //   dashboard to that branch;
        // - otherwise the dashboard aggregates at the organization level —
        //   the selected organization, or every organization on "All".
        // Model-level organization scopes follow ContextualOrganization, so
        // the payload below is already tenant-filtered when an org is active.
        $branchId = $request->integer('branch_id')
            ?: (int) ($request->session()->get('active_branch_id') ?? 0);
        $organizationId = ContextualOrganization::id();
        $today = Carbon::today();

        // Computed fresh on every request: cached aggregates went stale
        // whenever the underlying data changed (e.g. after a database wipe).
        $payload = (function () use ($branchId, $today) {
            // The dashboard owns its branch scope: drop the model-level
            // contextual-branch pin (organization scoping still applies) and
            // filter explicitly when a branch is selected.
            $branchScope = fn ($query) => $query
                ->withoutGlobalScope('branch')
                ->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId));

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

            // Room type breakdown — aggregated per type name so the same
            // type used in different branches shows as ONE row when the
            // dashboard spans multiple branches.
            $roomTypeBreakdown = Room::query()
                ->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
                ->select('room_type_id', DB::raw('count(*) as total'), DB::raw("sum(case when status = 'occupied' then 1 else 0 end) as occupied"))
                ->groupBy('room_type_id')
                ->with('roomType:id,name')
                ->get()
                ->groupBy(fn ($item) => $item->roomType?->name ?? 'Unknown')
                ->map(fn ($types, $name) => [
                    'type' => $name,
                    'total' => (int) $types->sum('total'),
                    'occupied' => (int) $types->sum('occupied'),
                ])
                ->sortBy('type')
                ->values();

            return [
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
            ];
        })();

        return Inertia::render('Dashboard', [
            ...$payload,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
            'scopeLabel' => $branchId > 0
                ? (HotelBranch::query()->whereKey($branchId)->value('name') ?? 'Selected branch')
                : ($organizationId !== null
                    ? (HotelOrganization::query()->whereKey($organizationId)->value('name') ?? 'Organization')
                    : 'All organizations'),
        ]);
    }
}

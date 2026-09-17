<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\ContextualBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()?->hasPermissionTo(PermissionName::ViewReports->value)) {
            abort(403);
        }

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $from = $request->date('from');
        $to = $request->date('to');

        $branchScope = fn ($query) => $query->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId));

        $totalRooms = $branchScope(Room::query())->count();
        $outOfService = $branchScope(Room::query())->where('status', 'out_of_service')->count();

        $reservations = $branchScope(Reservation::query())
            ->when($from, fn ($q) => $q->whereDate('check_in', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('check_in', '<=', $to));

        $reservationsTotal = (clone $reservations)->count();
        $reservationsCheckedIn = (clone $reservations)->where('status', 'checked_in')->count();
        $reservationsCancelled = (clone $reservations)->where('status', 'cancelled')->count();

        $occupancy = $totalRooms > 0 ? round(($reservationsCheckedIn / $totalRooms) * 100, 1) : 0.0;

        $revenuePaid = $branchScope(Payment::query())->where('status', 'completed')->sum('amount');
        $revenuePending = $branchScope(Payment::query())->where('status', 'pending')->sum('amount');

        $invoicesOutstanding = $branchScope(Invoice::query())
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->sum(DB::raw('total - amount_paid'));

        return Inertia::render('Reports/Index', [
            'metrics' => [
                'occupancy' => $occupancy,
                'total_rooms' => $totalRooms,
                'out_of_service' => $outOfService,
                'reservations' => $reservationsTotal,
                'checked_in' => $reservationsCheckedIn,
                'cancelled' => $reservationsCancelled,
                'revenue_paid' => $revenuePaid,
                'revenue_pending' => $revenuePending,
                'invoices_outstanding' => $invoicesOutstanding,
            ],
            'filters' => $request->only(['from', 'to', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }
}

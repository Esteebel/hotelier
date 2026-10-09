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

    public function export(Request $request)
    {
        if (! $request->user()?->hasPermissionTo(PermissionName::ViewReports->value)) {
            abort(403);
        }

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);
        $from = $request->date('from');
        $to = $request->date('to');
        $type = $request->string('type', 'reservations')->value;

        $branchScope = fn ($query) => $query->when($branchId > 0, fn ($q) => $q->where('branch_id', $branchId));

        $filename = "{$type}_report_" . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($branchScope, $from, $to, $type) {
            $handle = fopen('php://output', 'w');

            switch ($type) {
                case 'reservations':
                    fputcsv($handle, ['ID', 'Guest', 'Room', 'Check In', 'Check Out', 'Status', 'Total']);
                    $branchScope(Reservation::query())
                        ->with(['guest:id,first_name,last_name', 'room:id,room_number'])
                        ->when($from, fn ($q) => $q->whereDate('check_in', '>=', $from))
                        ->when($to, fn ($q) => $q->whereDate('check_in', '<=', $to))
                        ->orderBy('check_in', 'desc')
                        ->each(function ($r) use ($handle) {
                            fputcsv($handle, [
                                $r->reservation_number,
                                $r->guest?->first_name . ' ' . $r->guest?->last_name,
                                $r->room?->room_number,
                                $r->check_in,
                                $r->check_out,
                                $r->status,
                                number_format($r->total_amount, 2),
                            ]);
                        });
                    break;

                case 'payments':
                    fputcsv($handle, ['ID', 'Reservation', 'Amount', 'Method', 'Status', 'Date']);
                    $branchScope(Payment::query())
                        ->with('reservation:id,reservation_number')
                        ->orderBy('created_at', 'desc')
                        ->each(function ($p) use ($handle) {
                            fputcsv($handle, [
                                $p->payment_number,
                                $p->reservation?->reservation_number,
                                number_format($p->amount, 2),
                                $p->method,
                                $p->status,
                                $p->created_at->format('Y-m-d'),
                            ]);
                        });
                    break;

                case 'guests':
                    fputcsv($handle, ['Guest #', 'Name', 'Email', 'Phone', 'Reservations']);
                    $branchScope(\App\Models\Guest::query())
                        ->withCount('reservations')
                        ->orderBy('created_at', 'desc')
                        ->each(function ($g) use ($handle) {
                            fputcsv($handle, [
                                $g->guest_number,
                                $g->first_name . ' ' . $g->last_name,
                                $g->email,
                                $g->phone,
                                $g->reservations_count,
                            ]);
                        });
                    break;
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

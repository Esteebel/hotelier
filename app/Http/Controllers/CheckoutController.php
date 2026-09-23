<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Checkout;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Checkout::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $checkouts = Checkout::query()
            ->with([
                'branch:id,name',
                'reservation:id,reservation_number',
                'room:id,room_number',
                'guest:id,first_name,last_name',
                'processor:id,first_name,last_name',
            ])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('checkout_number', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('guest', fn ($x) => $x
                            ->where('first_name', 'like', '%'.$request->string('search').'%')
                            ->orWhere('last_name', 'like', '%'.$request->string('search').'%'))
                        ->orWhereHas('room', fn ($x) => $x->where('room_number', 'like', '%'.$request->string('search').'%')),
                ),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Checkouts/Index', [
            'checkouts' => $checkouts,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Checkout::class);

        $branchId = ContextualBranch::id();

        $reservations = Reservation::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'checked_in')
            ->with(['guest:id,first_name,last_name', 'room:id,room_number'])
            ->latest()
            ->get(['id', 'reservation_number', 'guest_id', 'room_id', 'check_out']);

        $rooms = Room::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', RoomStatus::Occupied->value)
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'room_type_id']);

        $guests = Guest::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Checkouts/Create', [
            'reservations' => $reservations,
            'rooms' => $rooms,
            'guests' => $guests,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $this->authorize('create', Checkout::class);

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['branch_id'] = ContextualBranch::id();
            $data['processed_by'] = $request->user()->id;
            $data['checked_out_at'] = now();
            $data['status'] = 'completed';

            // Calculate totals from reservation and payments
            $reservation = Reservation::with('payments')->findOrFail($data['reservation_id']);
            $totalPaid = $reservation->payments()->where('status', 'completed')->sum('amount');
            $balanceDue = max(0, $reservation->total_amount - $totalPaid);

            $data['total_charges'] = $reservation->total_amount;
            $data['total_paid'] = $totalPaid;
            $data['balance_due'] = $balanceDue;

            $checkout = Checkout::create($data);

            // Update reservation status
            Reservation::where('id', $data['reservation_id'])
                ->update(['status' => 'checked_out']);

            // Update room status to dirty (needs cleaning)
            Room::where('id', $data['room_id'])
                ->update(['status' => RoomStatus::Dirty->value]);

            DB::commit();

            return redirect()
                ->route('checkouts.index')
                ->with('success', 'Guest checked out successfully. Balance due: '.$data['balance_due']);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['checkout_number' => 'Check-out failed: '.$e->getMessage()]);
        }
    }

    public function show(Checkout $checkout): Response
    {
        $this->authorize('view', $checkout);

        $checkout->load([
            'branch:id,name',
            'reservation:id,reservation_number,check_in,check_out,total_amount',
            'room:id,room_number,room_type_id',
            'guest:id,first_name,last_name,email,phone',
            'processor:id,first_name,last_name',
        ]);

        return Inertia::render('Checkouts/Show', [
            'checkout' => $checkout,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function edit(Checkout $checkout): Response
    {
        $this->authorize('update', $checkout);

        $checkout->load([
            'reservation:id,reservation_number',
            'room:id,room_number',
            'guest:id,first_name,last_name',
        ]);

        return Inertia::render('Checkouts/Edit', [
            'checkout' => $checkout,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(CheckoutRequest $request, Checkout $checkout): RedirectResponse
    {
        $this->authorize('update', $checkout);

        $checkout->update($request->validated());

        return redirect()
            ->route('checkouts.index')
            ->with('success', 'Check-out updated successfully.');
    }

    public function destroy(Checkout $checkout): RedirectResponse
    {
        $this->authorize('delete', $checkout);

        DB::beginTransaction();

        try {
            // Restore room to occupied if checkout is being cancelled
            if ($checkout->status === 'completed') {
                Room::where('id', $checkout->room_id)
                    ->update(['status' => RoomStatus::Occupied->value]);

                Reservation::where('id', $checkout->reservation_id)
                    ->update(['status' => 'checked_in']);
            }

            $checkout->delete();

            DB::commit();

            return redirect()
                ->route('checkouts.index')
                ->with('success', 'Check-out cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['checkout_number' => 'Could not cancel check-out.']);
        }
    }
}

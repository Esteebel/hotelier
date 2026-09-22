<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Http\Requests\ExpenseRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Expense;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Expense::class);

        $transfers = Expense::query()
            ->with([
                'branch:id,name',
                'reservation:id,reservation_number',
                'fromRoom:id,room_number',
                'toRoom:id,room_number',
                'requester:id,first_name,last_name',
            ])
            ->forBranch(ContextualBranch::id())
            ->when(request('search'), fn ($q, $s) => $q
                ->where('transfer_number', 'like', "%{$s}%")
                ->orWhere('reason', 'like', "%{$s}%"))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('due_date')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('RoomTransfers/Index', [
            'transfers' => $transfers,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Expense::class);

        $branchId = ContextualBranch::id();

        $reservations = Reservation::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereIn('status', ['checked-in', 'pending'])
            ->with('room:id,room_number')
            ->latest()
            ->limit(200)
            ->get(['id', 'reservation_number', 'guest_id', 'room_id', 'status']);

        $rooms = Room::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'branch_id']);

        return Inertia::render('RoomTransfers/Create', [
            'reservations' => $reservations,
            'rooms' => $rooms,
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $data['transfer_number'] = Expense::nextTransferNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'pending';

        Expense::create($data);

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer requested.');
    }

    public function edit(Expense $roomTransfer): Response
    {
        $this->authorize('update', $roomTransfer);

        return Inertia::render('RoomTransfers/Edit', [
            'transfer' => $roomTransfer,
            'reservations' => Reservation::query()
                ->whereIn('status', ['checked-in', 'pending'])
                ->with('room:id,room_number')
                ->latest()
                ->limit(200)
                ->get(['id', 'reservation_number', 'guest_name', 'room_id', 'status']),
            'rooms' => Room::query()
                ->forBranch($roomTransfer->branch_id)
                ->orderBy('room_number')
                ->get(['id', 'room_number', 'branch_id']),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $roomTransfer): RedirectResponse
    {
        $this->authorize('update', $roomTransfer);

        $roomTransfer->update($request->validated());

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer updated.');
    }

    public function complete(Expense $roomTransfer): RedirectResponse
    {
        $this->authorize('update', $roomTransfer);

        $roomTransfer->forceFill([
            'status' => 'completed',
            'handled_by' => auth()->id(),
            'completed_at' => now(),
        ])->save();

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer completed.');
    }
}

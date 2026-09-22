<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Http\Requests\FoodOrderRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\FoodOrder;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FoodOrderController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', FoodOrder::class);

        $transfers = FoodOrder::query()
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
        $this->authorize('create', FoodOrder::class);

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

    public function store(FoodOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', FoodOrder::class);

        $data = $request->validated();
        $data['transfer_number'] = FoodOrder::nextTransferNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'pending';

        FoodOrder::create($data);

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer requested.');
    }

    public function edit(FoodOrder $roomTransfer): Response
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

    public function update(FoodOrderRequest $request, FoodOrder $roomTransfer): RedirectResponse
    {
        $this->authorize('update', $roomTransfer);

        $roomTransfer->update($request->validated());

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer updated.');
    }

    public function complete(FoodOrder $roomTransfer): RedirectResponse
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

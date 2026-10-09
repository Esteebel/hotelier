<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Http\Requests\RoomTransferRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoomTransferController extends Controller
{
    private const TRANSFERABLE_STATUSES = [
        ReservationStatus::CheckedIn->value,
        ReservationStatus::Extended->value,
    ];

    public function index(): Response
    {
        $this->authorize('viewAny', RoomTransfer::class);

        $transfers = RoomTransfer::query()
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
            ->latest('transfer_date')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('RoomTransfers/Index', [
            'transfers' => $transfers,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', RoomTransfer::class);

        $branchId = ContextualBranch::id();

        $reservations = Reservation::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereIn('status', self::TRANSFERABLE_STATUSES)
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

    public function store(RoomTransferRequest $request): RedirectResponse
    {
        $this->authorize('create', RoomTransfer::class);

        $data = $request->validated();
        $data['transfer_number'] = RoomTransfer::nextTransferNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['requested_by'] = $request->user()->id;
        $data['status'] = 'pending';

        RoomTransfer::create($data);

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer requested.');
    }

    public function edit(RoomTransfer $roomTransfer): Response
    {
        $this->authorize('update', $roomTransfer);

        return Inertia::render('RoomTransfers/Edit', [
            'transfer' => $roomTransfer,
            'reservations' => Reservation::query()
                ->when($roomTransfer->branch_id, fn ($query) => $query->where('branch_id', $roomTransfer->branch_id))
                ->whereIn('status', self::TRANSFERABLE_STATUSES)
                ->with('room:id,room_number')
                ->latest()
                ->limit(200)
                ->get(['id', 'reservation_number', 'guest_id', 'room_id', 'status']),
            'rooms' => Room::query()
                ->where('branch_id', $roomTransfer->branch_id)
                ->orderBy('room_number')
                ->get(['id', 'room_number', 'branch_id']),
        ]);
    }

    public function update(RoomTransferRequest $request, RoomTransfer $roomTransfer): RedirectResponse
    {
        $this->authorize('update', $roomTransfer);

        $data = $request->validated();
        $wantsComplete = ($data['status'] ?? null) === 'completed';
        unset($data['status']);

        $roomTransfer->update($data);

        if ($wantsComplete) {
            return $this->complete($roomTransfer);
        }

        $roomTransfer->update(['status' => $request->input('status')]);

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer updated.');
    }

    public function complete(RoomTransfer $roomTransfer): RedirectResponse
    {
        $this->authorize('update', $roomTransfer);

        if ($roomTransfer->status === 'completed') {
            return redirect()
                ->route('room-transfers.index')
                ->with('success', 'Room transfer already completed.');
        }

        $toRoom = $roomTransfer->to_room_id
            ? Room::find($roomTransfer->to_room_id)
            : null;

        $blocked = [
            RoomStatus::Occupied->value,
            RoomStatus::Reserved->value,
            RoomStatus::Maintenance->value,
            RoomStatus::OutOfService->value,
        ];

        if ($roomTransfer->to_room_id && ($toRoom === null || in_array($toRoom->status->value, $blocked, true))) {
            return redirect()
                ->back()
                ->withErrors(['to_room_id' => 'The destination room is not available for transfer.']);
        }

        DB::transaction(function () use ($roomTransfer) {
            $roomTransfer->forceFill([
                'status' => 'completed',
                'handled_by' => auth()->id(),
            ])->save();

            if ($roomTransfer->from_room_id) {
                $fromRoom = Room::find($roomTransfer->from_room_id);

                if ($fromRoom && $fromRoom->status === RoomStatus::Occupied) {
                    $fromRoom->update(['status' => RoomStatus::Dirty]);
                }
            }

            if ($roomTransfer->to_room_id && $roomTransfer->reservation_id) {
                $reservation = Reservation::withoutGlobalScope('branch')
                    ->where('id', $roomTransfer->reservation_id)
                    ->where('branch_id', $roomTransfer->branch_id)
                    ->first();

                if (
                    $reservation
                    && in_array($reservation->status->value, self::TRANSFERABLE_STATUSES, true)
                ) {
                    Room::where('id', $roomTransfer->to_room_id)
                        ->update(['status' => RoomStatus::Occupied->value]);
                    $reservation->update(['room_id' => $roomTransfer->to_room_id]);
                }
            }
        });

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer completed.');
    }

    public function destroy(RoomTransfer $roomTransfer): RedirectResponse
    {
        $this->authorize('delete', $roomTransfer);

        $roomTransfer->delete();

        return redirect()
            ->route('room-transfers.index')
            ->with('success', 'Room transfer deleted.');
    }
}

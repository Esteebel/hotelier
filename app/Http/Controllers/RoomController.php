<?php

namespace App\Http\Controllers;

use App\Events\RoomStatusChanged;
use App\Http\Requests\RoomRequest;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Room::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $rooms = Room::query()
            ->with('roomType:id,name,base_price')
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('room_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('floor', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn (Room $room) => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'floor' => $room->floor,
                    'capacity' => $room->capacity,
                    'price' => $room->price ?? $room->roomType?->base_price,
                    'status' => $room->status->value,
                    'status_label' => $room->status_label,
                    'badge_class' => $room->status->badgeClass(),
                    'is_active' => $room->is_active,
                    'room_type' => $room->roomType?->only(['id', 'name']),
                ],
            );

        return Inertia::render('Rooms/Index', [
            'rooms' => $rooms,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Room::class);

        return Inertia::render('Rooms/Create', [
            'roomTypes' => $this->roomTypeOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $this->authorize('create', Room::class);

        Room::create($request->validated());

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room created successfully.');
    }

    public function edit(Room $room): Response
    {
        $this->authorize('update', $room);

        return Inertia::render('Rooms/Edit', [
            'room' => [
                'id' => $room->id,
                'branch_id' => $room->branch_id,
                'room_type_id' => $room->room_type_id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'capacity' => $room->capacity,
                'price' => $room->price,
                'status' => $room->status->value,
                'description' => $room->description,
                'amenities' => $room->amenities,
                'is_active' => $room->is_active,
            ],
            'roomTypes' => $this->roomTypeOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $this->authorize('update', $room);

        $oldStatus = $room->status?->value;

        $room->update($request->validated());

        if ($oldStatus !== null && $oldStatus !== $room->status?->value) {
            RoomStatusChanged::dispatch($room, $oldStatus, $room->status->value);
        }

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        $room->delete();

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room deleted successfully.');
    }

    /**
     * Room types for the current branch context.
     *
     * @return array<int, array{id: int, name: string, base_price: string}>
     */
    private function roomTypeOptions(): array
    {
        $branchId = ContextualBranch::id();

        return RoomType::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'base_price'])
            ->map(fn (RoomType $type) => $type->only(['id', 'name', 'base_price']))
            ->all();
    }
}

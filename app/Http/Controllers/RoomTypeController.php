<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomTypeRequest;
use App\Models\RoomType;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RoomType::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $roomTypes = RoomType::query()
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'),
            )
            ->withCount('rooms')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(
                fn (RoomType $roomType) => [
                    'id' => $roomType->id,
                    'name' => $roomType->name,
                    'description' => $roomType->description,
                    'base_price' => $roomType->base_price,
                    'capacity' => $roomType->capacity,
                    'number_of_beds' => $roomType->number_of_beds,
                    'bed_configuration' => $roomType->bed_configuration,
                    'is_active' => $roomType->is_active,
                    'rooms_count' => $roomType->rooms_count,
                ],
            );

        return Inertia::render('RoomTypes/Index', [
            'roomTypes' => $roomTypes,
            'filters' => $request->only(['search', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', RoomType::class);

        return Inertia::render('RoomTypes/Create', [
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(RoomTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', RoomType::class);

        RoomType::create($request->validated());

        return redirect()
            ->route('room-types.index')
            ->with('success', 'Room type created successfully.');
    }

    public function edit(RoomType $roomType): Response
    {
        $this->authorize('update', $roomType);

        return Inertia::render('RoomTypes/Edit', [
            'roomType' => [
                'id' => $roomType->id,
                'branch_id' => $roomType->branch_id,
                'name' => $roomType->name,
                'description' => $roomType->description,
                'base_price' => $roomType->base_price,
                'capacity' => $roomType->capacity,
                'number_of_beds' => $roomType->number_of_beds,
                'bed_configuration' => $roomType->bed_configuration,
                'is_active' => $roomType->is_active,
            ],
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $this->authorize('update', $roomType);

        $roomType->update($request->validated());

        return redirect()
            ->route('room-types.index')
            ->with('success', 'Room type updated successfully.');
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        $this->authorize('delete', $roomType);

        $roomType->delete();

        return redirect()
            ->route('room-types.index')
            ->with('success', 'Room type deleted successfully.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Requests\LaundryOrderRequest;
use App\Models\LaundryOrder;
use App\Models\Room;
use App\Models\Guest;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaundryOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LaundryOrder::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $orders = LaundryOrder::query()
            ->with(['branch:id,name', 'room:id,room_number', 'guest:id,first_name,last_name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q->where('order_number', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Laundry/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LaundryOrder::class);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);
        $guests = Guest::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Laundry/Create', [
            'rooms' => $rooms,
            'guests' => $guests,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(LaundryOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', LaundryOrder::class);

        $data = $request->validated();
        $data['order_number'] = LaundryOrder::nextOrderNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending';

        LaundryOrder::create($data);

        return redirect()->route('laundry.index')->with('success', 'Laundry order created.');
    }

    public function edit(LaundryOrder $laundryOrder): Response
    {
        $this->authorize('update', $laundryOrder);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);
        $guests = Guest::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Laundry/Edit', [
            'order' => $laundryOrder,
            'rooms' => $rooms,
            'guests' => $guests,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(LaundryOrderRequest $request, LaundryOrder $laundryOrder): RedirectResponse
    {
        $this->authorize('update', $laundryOrder);

        $laundryOrder->update($request->validated());

        return redirect()->route('laundry.index')->with('success', 'Laundry order updated.');
    }

    public function complete(LaundryOrder $laundryOrder): RedirectResponse
    {
        $this->authorize('update', $laundryOrder);

        $laundryOrder->update(['status' => 'completed']);

        return redirect()->route('laundry.index')->with('success', 'Laundry order completed.');
    }

    public function destroy(LaundryOrder $laundryOrder): RedirectResponse
    {
        $this->authorize('delete', $laundryOrder);

        $laundryOrder->delete();

        return redirect()->route('laundry.index')->with('success', 'Laundry order deleted.');
    }
}

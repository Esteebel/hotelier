<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodOrderRequest;
use App\Models\FoodOrder;
use App\Models\MenuItem;
use App\Models\Room;
use App\Models\Guest;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoodOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FoodOrder::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $orders = FoodOrder::query()
            ->with(['branch:id,name', 'room:id,room_number', 'guest:id,first_name,last_name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('order_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('order_type', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Restaurant/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FoodOrder::class);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);
        $guests = Guest::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $menuItems = MenuItem::query()->forBranch($branchId)->available()->orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('Restaurant/Create', [
            'rooms' => $rooms,
            'guests' => $guests,
            'menuItems' => $menuItems,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(FoodOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', FoodOrder::class);

        $data = $request->validated();
        $composed = MenuItem::composeOrder($data['items']);
        if (! $composed['items']) {
            return back()->withErrors(['items' => 'Please select at least one available menu item.'])->withInput();
        }

        $data['items'] = $composed['items'];
        $data['total_amount'] = $composed['total_amount'];
        $data['order_number'] = FoodOrder::nextOrderNumber();
        $data['branch_id'] = ContextualBranch::id();
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending';

        FoodOrder::create($data);

        return redirect()->route('restaurant.index')->with('success', 'Food order created.');
    }

    public function edit(FoodOrder $foodOrder): Response
    {
        $this->authorize('update', $foodOrder);

        $branchId = ContextualBranch::id();
        $rooms = Room::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('room_number')->get(['id', 'room_number']);
        $guests = Guest::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $menuItems = MenuItem::query()->forBranch($branchId)->available()->orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('Restaurant/Edit', [
            'order' => $foodOrder,
            'rooms' => $rooms,
            'guests' => $guests,
            'menuItems' => $menuItems,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(FoodOrderRequest $request, FoodOrder $foodOrder): RedirectResponse
    {
        $this->authorize('update', $foodOrder);

        $data = $request->validated();
        $composed = MenuItem::composeOrder($data['items'], $foodOrder->branch_id);
        if (! $composed['items']) {
            return back()->withErrors(['items' => 'Please select at least one available menu item.'])->withInput();
        }

        $data['items'] = $composed['items'];
        $data['total_amount'] = $composed['total_amount'];

        $foodOrder->update($data);

        return redirect()->route('restaurant.index')->with('success', 'Food order updated.');
    }

    public function complete(FoodOrder $foodOrder): RedirectResponse
    {
        $this->authorize('update', $foodOrder);

        $foodOrder->update(['status' => 'completed']);

        return redirect()->route('restaurant.index')->with('success', 'Food order completed.');
    }

    public function destroy(FoodOrder $foodOrder): RedirectResponse
    {
        $this->authorize('delete', $foodOrder);

        $foodOrder->delete();

        return redirect()->route('restaurant.index')->with('success', 'Food order deleted.');
    }
}

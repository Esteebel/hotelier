<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuItemRequest;
use App\Models\MenuItem;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuItemController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MenuItem::class);

        $branchId = ContextualBranch::id();

        $menuItems = MenuItem::query()
            ->forBranch($branchId)
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('category', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_available', $request->string('status') === 'available');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Restaurant/Menu/Index', [
            'menuItems' => $menuItems,
            'categories' => MenuItem::categories(),
            'filters' => $request->only(['search', 'category', 'status']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', MenuItem::class);

        return Inertia::render('Restaurant/Menu/Create', [
            'categories' => MenuItem::categories(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(MenuItemRequest $request): RedirectResponse
    {
        $this->authorize('create', MenuItem::class);

        $data = $request->validated();
        $data['branch_id'] = ContextualBranch::id();
        $data['is_available'] = $request->boolean('is_available', true);

        MenuItem::create($data);

        return redirect()
            ->route('menu.index')
            ->with('success', 'Menu item created successfully.');
    }

    public function edit(MenuItem $menuItem): Response
    {
        $this->authorize('update', $menuItem);

        return Inertia::render('Restaurant/Menu/Edit', [
            'menuItem' => $this->serialize($menuItem),
            'categories' => MenuItem::categories(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('update', $menuItem);

        $data = $request->validated();
        $data['is_available'] = $request->boolean('is_available');

        $menuItem->update($data);

        return redirect()
            ->route('menu.index')
            ->with('success', 'Menu item updated successfully.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('delete', $menuItem);

        $menuItem->delete();

        return redirect()
            ->route('menu.index')
            ->with('success', 'Menu item deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(MenuItem $menuItem): array
    {
        return [
            'id' => $menuItem->id,
            'branch_id' => $menuItem->branch_id,
            'name' => $menuItem->name,
            'category' => $menuItem->category,
            'description' => $menuItem->description,
            'price' => $menuItem->price,
            'is_available' => $menuItem->is_available,
            'sort_order' => $menuItem->sort_order,
        ];
    }
}

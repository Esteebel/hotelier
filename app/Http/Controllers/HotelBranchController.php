<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelBranchRequest;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelBranchController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HotelBranch::class);

        $branches = HotelBranch::query()
            ->with('organization:id,name')
            ->when(
                $request->filled('organization_id'),
                fn ($query) => $query->where('organization_id', $request->integer('organization_id')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('code', 'like', '%'.$request->string('search').'%')
                        ->orWhere('city', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->withCount('rooms')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(
                fn (HotelBranch $branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'city' => $branch->city,
                    'state' => $branch->state,
                    'star_rating' => $branch->star_rating,
                    'status' => $branch->status->value,
                    'status_label' => $branch->status_label,
                    'is_default' => $branch->is_default,
                    'rooms_count' => $branch->rooms_count,
                    'organization' => $branch->organization?->only(['id', 'name']),
                ],
            );

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'organizations' => $this->organizationOptions(),
            'filters' => $request->only(['search', 'status', 'organization_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', HotelBranch::class);

        return Inertia::render('Branches/Create', [
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function store(HotelBranchRequest $request): RedirectResponse
    {
        $this->authorize('create', HotelBranch::class);

        HotelBranch::create($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function edit(HotelBranch $branch): Response
    {
        $this->authorize('update', $branch);

        return Inertia::render('Branches/Edit', [
            'branch' => $this->serialize($branch),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function update(HotelBranchRequest $request, HotelBranch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $branch->update($request->validated());

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(HotelBranch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch deleted successfully.');
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch' => ['required', 'integer', 'exists:hotel_branches,id'],
        ]);

        $this->authorize('view', HotelBranch::findOrFail($validated['branch']));

        $request->session()->put('active_branch_id', $validated['branch']);

        return redirect()->back()
            ->with('success', 'Branch switched successfully.');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function organizationOptions(): array
    {
        return HotelOrganization::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (HotelOrganization $org) => $org->only(['id', 'name']))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(HotelBranch $branch): array
    {
        return [
            'id' => $branch->id,
            'organization_id' => $branch->organization_id,
            'name' => $branch->name,
            'code' => $branch->code,
            'description' => $branch->description,
            'address' => $branch->address,
            'city' => $branch->city,
            'state' => $branch->state,
            'country' => $branch->country,
            'postal_code' => $branch->postal_code,
            'phone' => $branch->phone,
            'email' => $branch->email,
            'currency' => $branch->currency,
            'star_rating' => $branch->star_rating,
            'check_in_time' => $branch->check_in_time,
            'check_out_time' => $branch->check_out_time,
            'status' => $branch->status->value,
            'is_default' => $branch->is_default,
        ];
    }
}
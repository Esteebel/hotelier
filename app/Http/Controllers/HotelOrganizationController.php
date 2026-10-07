<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelOrganizationRequest;
use App\Models\HotelOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelOrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HotelOrganization::class);

        $organizations = HotelOrganization::query()
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
            ->withCount('branches')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(
                fn (HotelOrganization $hotel) => [
                    'id' => $hotel->id,
                    'name' => $hotel->name,
                    'slug' => $hotel->slug,
                    'code' => $hotel->code,
                    'city' => $hotel->city,
                    'state' => $hotel->state,
                    'star_rating' => $hotel->star_rating,
                    'status' => $hotel->status->value,
                    'status_label' => $hotel->status_label,
                    'branches_count' => $hotel->branches_count,
                ],
            );

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', HotelOrganization::class);

        return Inertia::render('Organizations/Create');
    }

    public function store(HotelOrganizationRequest $request): RedirectResponse
    {
        $this->authorize('create', HotelOrganization::class);

        HotelOrganization::create($request->validated());

        return redirect()
            ->route('organizations.index')
            ->with('success', 'Hotel organization created successfully.');
    }

    public function edit(HotelOrganization $organization): Response
    {
        $this->authorize('update', $organization);

        return Inertia::render('Organizations/Edit', [
            'organization' => $this->serialize($organization),
        ]);
    }

    public function update(HotelOrganizationRequest $request, HotelOrganization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return redirect()
            ->route('organizations.index')
            ->with('success', 'Hotel organization updated successfully.');
    }

    public function destroy(Request $request, HotelOrganization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        if ((int) $request->session()->get('active_organization_id') === $organization->id) {
            $request->session()->forget('active_organization_id');
        }

        $organization->delete();

        return redirect()
            ->route('organizations.index')
            ->with('success', 'Hotel organization deleted successfully.');
    }

    /**
     * Navbar organization switcher: narrows the whole admin panel to one
     * organization, or back to "All organizations" when null is given.
     */
    public function switch(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null && ! $user->isCustomer() && ! $user->isOrgScoped(), 403);

        $data = $request->validate([
            'organization' => ['nullable', 'integer', 'exists:hotel_organizations,id'],
        ]);

        $organizationId = $data['organization'] ?? null;
        $message = 'Showing all organizations.';

        if ($organizationId !== null) {
            $name = HotelOrganization::query()
                ->withoutGlobalScope('organization')
                ->whereKey($organizationId)
                ->value('name');

            $request->session()->put('active_organization_id', (int) $organizationId);
            $message = 'Now showing '.$name.' only.';
        } else {
            $request->session()->forget('active_organization_id');
        }

        // Branch selections from another organization must not survive.
        $request->session()->forget('active_branch_id');

        return redirect()->back()->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(HotelOrganization $hotel): array
    {
        return [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'code' => $hotel->code,
            'description' => $hotel->description,
            'address' => $hotel->address,
            'city' => $hotel->city,
            'state' => $hotel->state,
            'country' => $hotel->country,
            'postal_code' => $hotel->postal_code,
            'phone' => $hotel->phone,
            'email' => $hotel->email,
            'currency' => $hotel->currency,
            'star_rating' => $hotel->star_rating,
            'check_in_time' => $hotel->check_in_time,
            'check_out_time' => $hotel->check_out_time,
            'status' => $hotel->status->value,
        ];
    }
}
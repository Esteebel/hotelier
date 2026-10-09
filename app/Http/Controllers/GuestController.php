<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuestRequest;
use App\Models\Guest;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Guest::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $guests = Guest::query()
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('first_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('last_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('email', 'like', '%'.$request->string('search').'%')
                        ->orWhere('phone', 'like', '%'.$request->string('search').'%')
                        ->orWhere('guest_number', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn (Guest $guest) => [
                    'id' => $guest->id,
                    'guest_number' => $guest->guest_number,
                    'name' => $guest->full_name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                    'nationality' => $guest->nationality,
                    'status' => $guest->status->value,
                    'status_label' => $guest->status_label,
                    'badge_class' => $guest->status->badgeClass(),
                    'is_vip' => $guest->is_vip,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'id_number' => $guest->id_number,
                    'id_type' => $guest->id_type,
                ],
            );

        return Inertia::render('Guests/Index', [
            'guests' => $guests,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Guest::class);

        return Inertia::render('Guests/Create', [
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(GuestRequest $request): RedirectResponse
    {
        $this->authorize('create', Guest::class);

        Guest::create($request->validated());

        return redirect()
            ->route('guests.index')
            ->with('success', 'Guest created successfully.');
    }

    public function edit(Guest $guest): Response
    {
        $this->authorize('update', $guest);

        return Inertia::render('Guests/Edit', [
            'guest' => [
                'id' => $guest->id,
                'branch_id' => $guest->branch_id,
                'first_name' => $guest->first_name,
                'last_name' => $guest->last_name,
                'email' => $guest->email,
                'phone' => $guest->phone,
                'birth_date' => $guest->birth_date?->toDateString(),
                'nationality' => $guest->nationality,
                'id_number' => $guest->id_number,
                'id_type' => $guest->id_type,
                'address' => $guest->address,
                'company' => $guest->company,
                'notes' => $guest->notes,
                'is_vip' => $guest->is_vip,
                'status' => $guest->status->value,
            ],
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(GuestRequest $request, Guest $guest): RedirectResponse
    {
        $this->authorize('update', $guest);

        $guest->update($request->validated());

        return redirect()
            ->route('guests.index')
            ->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        $this->authorize('delete', $guest);

        $guest->delete();

        return redirect()
            ->route('guests.index')
            ->with('success', 'Guest deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\ContextualBranch;
use App\Support\GuestNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reservation::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $reservations = Reservation::query()
            ->with(['guest:id,first_name,last_name', 'room:id,room_number'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->whereHas(
                    'guest',
                    fn ($q) => $q->where(fn ($x) => $x
                        ->where('first_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('last_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('email', 'like', '%'.$request->string('search').'%'))
                    ->orWhere('reservation_number', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn (Reservation $reservation) => [
                    'id' => $reservation->id,
                    'code' => $reservation->reservation_number,
                    'guest' => $reservation->guest?->full_name,
                    'room' => $reservation->room?->room_number,
                    'check_in' => $reservation->check_in->toDateString(),
                    'check_out' => $reservation->check_out->toDateString(),
                    'nights' => $reservation->nights,
                    'total_amount' => $reservation->total_amount,
                    'status' => $reservation->status->value,
                    'status_label' => $reservation->status_label,
                    'badge_class' => $reservation->status->badgeClass(),
                ],
            );

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Reservation::class);

        return Inertia::render('Reservations/Create', [
            'guests' => $this->guestOptions(),
            'rooms' => $this->roomOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        $this->authorize('create', Reservation::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()?->id;
        $data['special_requests'] = $data['special_requests'] ?? ($data['notes'] ?? null);
        unset($data['notes']);

        $reservation = Reservation::create($data);

        GuestNotifier::notify(
            $reservation->guest,
            'Reservation confirmed',
            'Your reservation '.$reservation->reservation_number.' ('.$reservation->check_in->toDateString()
                .' to '.$reservation->check_out->toDateString().') has been confirmed.',
            'success',
        );

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation created successfully.');
    }

    public function edit(Reservation $reservation): Response
    {
        $this->authorize('update', $reservation);

        return Inertia::render('Reservations/Edit', [
            'reservation' => [
                'id' => $reservation->id,
                'branch_id' => $reservation->branch_id,
                'guest_id' => $reservation->guest_id,
                'room_id' => $reservation->room_id,
                'reservation_number' => $reservation->reservation_number,
                'check_in' => $reservation->check_in->toDateString(),
                'check_out' => $reservation->check_out->toDateString(),
                'adults' => $reservation->adults,
                'children' => $reservation->children,
                'rate_plan' => $reservation->rate_plan,
                'total_amount' => $reservation->total_amount,
                'balance_due' => $reservation->balance_due,
                'notes' => $reservation->special_requests,
                'special_requests' => $reservation->special_requests,
                'status' => $reservation->status->value,
            ],
            'guests' => $this->guestOptions(),
            'rooms' => $this->roomOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(ReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('update', $reservation);

        $reservation->update($request->validated());

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation updated successfully.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $this->authorize('delete', $reservation);

        $reservation->delete();

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Reservation deleted successfully.');
    }

    /**
     * @return array<int, array{id: int, full_name: string}>
     */
    private function guestOptions(): array
    {
        return Guest::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Guest $guest) => $guest->only(['id', 'full_name']))
            ->all();
    }

    /**
     * @return array<int, array{id: int, room_number: string}>
     */
    private function roomOptions(): array
    {
        $branchId = ContextualBranch::id();

        return Room::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'available')
            ->where('is_active', true)
            ->orderBy('room_number')
            ->get(['id', 'room_number'])
            ->map(fn (Room $room) => $room->only(['id', 'room_number']))
            ->all();
    }
}

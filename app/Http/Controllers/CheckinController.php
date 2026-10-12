<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Events\CheckinCompleted;
use App\Http\Requests\CheckinRequest;
use App\Models\Checkin;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\ContextualBranch;
use App\Support\GuestNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckinController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Checkin::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $checkins = Checkin::query()
            ->with([
                'branch:id,name',
                'reservation:id,reservation_number',
                'room:id,room_number',
                'guest:id,first_name,last_name',
                'processor:id,name',
            ])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('checkin_number', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('guest', fn ($x) => $x
                            ->where('first_name', 'like', '%'.$request->string('search').'%')
                            ->orWhere('last_name', 'like', '%'.$request->string('search').'%'))
                        ->orWhereHas('room', fn ($x) => $x->where('room_number', 'like', '%'.$request->string('search').'%')),
                ),
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Checkins/Index', [
            'checkins' => $checkins,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Checkin::class);

        $branchId = ContextualBranch::id();

        $reservations = Reservation::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'confirmed')
            ->with('guest:id,first_name,last_name')
            ->latest()
            ->get(['id', 'reservation_number', 'guest_id', 'room_id', 'check_in']);

        $rooms = Room::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereIn('id', $reservations->pluck('room_id')->filter()->unique())
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'room_type_id']);

        $guests = Guest::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Checkins/Create', [
            'reservations' => $reservations,
            'rooms' => $rooms,
            'guests' => $guests,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(CheckinRequest $request): RedirectResponse
    {
        $this->authorize('create', Checkin::class);

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['branch_id'] = ContextualBranch::id();
            $data['processed_by'] = $request->user()->id;
            $data['checked_in_at'] = now();
            $data['status'] = 'checked_in';

            $reservation = Reservation::withoutGlobalScope('branch')
                ->findOrFail($data['reservation_id']);

            $checkin = Checkin::create($data);

            // Reservation: checked in, and the room actually used is recorded
            // (covers legacy reservations created without a room).
            $reservation->update([
                'status' => 'checked_in',
                'room_id' => $data['room_id'],
            ]);

            // Room occupied — only from a non-blocked state.
            Room::withoutGlobalScope('organization')
                ->where('id', $data['room_id'])
                ->whereIn('status', ['available', 'reserved', 'dirty', 'cleaning'])
                ->update(['status' => RoomStatus::Occupied->value]);

            GuestNotifier::notify(
                Guest::find($data['guest_id']),
                'Checked in',
                'You have been checked in against reservation '.$reservation->reservation_number
                    .'. Enjoy your stay.',
                'info',
            );

            CheckinCompleted::dispatch($checkin, $data['branch_id']);

            DB::commit();

            return redirect()
                ->route('checkins.index')
                ->with('success', 'Guest checked in successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['checkin_number' => 'Check-in failed: '.$e->getMessage()]);
        }
    }

    public function show(Checkin $checkin): Response
    {
        $this->authorize('view', $checkin);

        $checkin->load([
            'branch:id,name',
            'reservation:id,reservation_number,check_in,check_out,total_amount',
            'room:id,room_number,room_type_id',
            'guest:id,first_name,last_name,email,phone',
            'processor:id,name',
        ]);

        return Inertia::render('Checkins/Show', [
            'checkin' => $checkin,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function edit(Checkin $checkin): Response
    {
        $this->authorize('update', $checkin);

        $checkin->load([
            'reservation:id,reservation_number',
            'room:id,room_number',
            'guest:id,first_name,last_name',
        ]);

        return Inertia::render('Checkins/Edit', [
            'checkin' => $checkin,
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(CheckinRequest $request, Checkin $checkin): RedirectResponse
    {
        $this->authorize('update', $checkin);

        $checkin->update($request->validated());

        return redirect()
            ->route('checkins.index')
            ->with('success', 'Check-in updated successfully.');
    }

    public function complete(Checkin $checkin): RedirectResponse
    {
        $this->authorize('update', $checkin);

        $checkin->forceFill([
            'status' => 'completed',
            'processed_by' => auth()->id(),
        ])->save();

        return redirect()
            ->route('checkins.index')
            ->with('success', 'Check-in marked complete.');
    }

    public function destroy(Checkin $checkin): RedirectResponse
    {
        $this->authorize('delete', $checkin);

        DB::beginTransaction();

        try {
            // Restore room to reserved status if checkin is being cancelled
            if ($checkin->status === 'checked_in') {
                Room::where('id', $checkin->room_id)
                    ->update(['status' => RoomStatus::Reserved->value]);

                Reservation::where('id', $checkin->reservation_id)
                    ->update(['status' => 'confirmed']);
            }

            $checkin->delete();

            DB::commit();

            return redirect()
                ->route('checkins.index')
                ->with('success', 'Check-in cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['checkin_number' => 'Could not cancel check-in.']);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FoodOrder;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\ContextualBranch;
use App\Support\GuestNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function home(Request $request): Response
    {
        $user = $request->user();
        $guest = $user->guest;

        $reservations = $guest
            ? $this->reservationsQuery($guest)->get()
            : collect();

        $invoices = $guest
            ? $this->invoicesQuery($guest)->get()
            : collect();

        $activeReservation = $reservations
            ->filter(fn (Reservation $reservation) => in_array($reservation->status?->value, ['confirmed', 'checked_in'], true))
            ->sortBy('check_in')
            ->first();

        $balanceDue = $invoices->sum(fn (Invoice $invoice) => max(0, (float) $invoice->total - (float) $invoice->amount_paid));

        $pendingOrders = $this->ordersQuery($user)
            ->whereIn('status', ['pending', 'in-progress', 'ready'])
            ->count();

        return Inertia::render('Portal/Home', [
            'guest' => $guest?->only(['id', 'first_name', 'last_name', 'email', 'phone']),
            'stats' => [
                'upcoming_reservations' => $reservations
                    ->filter(fn (Reservation $reservation) => in_array($reservation->status?->value, ['confirmed', 'checked_in'], true))
                    ->count(),
                'balance_due' => round($balanceDue, 2),
                'pending_orders' => $pendingOrders,
                'total_stays' => $reservations->count(),
            ],
            'activeReservation' => $activeReservation
                ? $this->serializeReservation($activeReservation)
                : null,
        ]);
    }

    public function menu(Request $request): Response
    {
        $user = $request->user();
        $branchId = $user->guest?->branch_id ?? ContextualBranch::id();

        $menuItems = MenuItem::query()
            ->forBranch($branchId)
            ->available()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rooms = $user->guest
            ? Room::query()
                ->whereHas('reservations', function ($query) use ($user) {
                    $query->withoutGlobalScope('branch')
                        ->where('guest_id', $user->guest_id)
                        ->whereIn('status', ['confirmed', 'checked_in']);
                })
                ->orderBy('room_number')
                ->get(['id', 'room_number'])
            : collect();

        return Inertia::render('Portal/Menu', [
            'guest' => $user->guest?->only(['id', 'first_name', 'last_name']),
            'menuItems' => $menuItems,
            'rooms' => $rooms,
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_type' => ['required', 'in:dine_in,takeout,delivery'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $branchId = $user->guest?->branch_id ?? ContextualBranch::id();

        $composed = MenuItem::composeOrder($data['items'], $branchId);
        if (! $composed['items']) {
            return back()->withErrors(['items' => 'Please select at least one available menu item.'])->withInput();
        }

        FoodOrder::create([
            'order_number' => FoodOrder::nextOrderNumber(),
            'branch_id' => $branchId,
            'room_id' => $data['room_id'] ?? null,
            'guest_id' => $user->guest_id,
            'order_type' => $data['order_type'],
            'items' => $composed['items'],
            'total_amount' => $composed['total_amount'],
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('portal.orders')->with('success', 'Your order has been placed.');
    }

    public function orders(Request $request): Response
    {
        $orders = $this->ordersQuery($request->user())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Portal/Orders', [
            'orders' => $orders,
        ]);
    }

    public function rooms(Request $request): Response
    {
        $user = $request->user();
        $guest = $user->guest;
        $branchId = $guest?->branch_id ?? ContextualBranch::id();

        $data = $request->validate([
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date'],
        ]);

        $checkIn = isset($data['check_in'])
            ? Carbon::parse($data['check_in'])->startOfDay()
            : Carbon::now()->startOfDay();
        $checkOut = isset($data['check_out'])
            ? Carbon::parse($data['check_out'])->startOfDay()
            : $checkIn->copy()->addDay();

        if ($checkOut->lte($checkIn)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        $nights = max(1, (int) $checkIn->diffInDays($checkOut));

        $rooms = Room::query()
            ->with('roomType:id,name,base_price')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNotIn('status', ['maintenance', 'out_of_service'])
            ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
                $query->withoutGlobalScope('branch')
                    ->whereIn('status', ['confirmed', 'checked_in', 'extended'])
                    ->where('check_in', '<', $checkOut->toDateString())
                    ->where('check_out', '>', $checkIn->toDateString());
            })
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'capacity' => (int) $room->capacity,
                'price' => round((float) ($room->price ?? $room->roomType?->base_price ?? 0), 2),
                'status' => $room->status->value,
                'status_label' => $room->status_label,
                'description' => $room->description,
                'room_type' => $room->roomType?->only(['id', 'name']),
                'total' => round(
                    $nights * (float) ($room->price ?? $room->roomType?->base_price ?? 0),
                    2,
                ),
            ]);

        return Inertia::render('Portal/Rooms', [
            'guest' => $guest?->only(['id', 'first_name', 'last_name']),
            'rooms' => $rooms,
            'nights' => $nights,
            'filters' => [
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
            ],
        ]);
    }

    public function storeReservation(Request $request): RedirectResponse
    {
        $user = $request->user();
        $guest = $user->guest;

        if (! $guest) {
            return back()->withErrors([
                'room_id' => 'Please contact reception to link a guest profile to your account before booking.',
            ])->withInput();
        }

        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ]);

        $children = (int) ($data['children'] ?? 0);
        $party = (int) $data['adults'] + $children;

        $room = Room::query()
            ->where('branch_id', $guest->branch_id)
            ->where('is_active', true)
            ->whereNotIn('status', ['maintenance', 'out_of_service'])
            ->find($data['room_id']);

        if (! $room) {
            return back()->withErrors([
                'room_id' => 'That room is not available for booking.',
            ])->withInput();
        }

        if ($party > (int) $room->capacity) {
            return back()->withErrors([
                'room_id' => "Room {$room->room_number} sleeps up to {$room->capacity} guests.",
            ])->withInput();
        }

        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();

        $clash = $room->reservations()
            ->withoutGlobalScope('branch')
            ->whereIn('status', ['confirmed', 'checked_in', 'extended'])
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->exists();

        if ($clash) {
            return back()->withErrors([
                'room_id' => 'Room '.$room->room_number.' is not available for the selected dates. Please pick different dates or another room.',
            ])->withInput();
        }

        $nights = max(1, (int) $checkIn->diffInDays($checkOut));
        $nightly = (float) ($room->price ?? $room->roomType?->base_price ?? 0);
        $total = round($nights * $nightly, 2);

        $reservation = Reservation::create([
            'branch_id' => $room->branch_id,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'reservation_number' => $this->nextReservationNumber($room),
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'adults' => (int) $data['adults'],
            'children' => $children,
            'status' => 'confirmed',
            'total_amount' => $total,
            'balance_due' => $total,
            'special_requests' => $data['special_requests'] ?? null,
            'created_by' => $user->id,
        ]);

        GuestNotifier::notify(
            $guest,
            'Reservation confirmed',
            'Your reservation '.$reservation->reservation_number.' for room '.$room->room_number
                .' ('.$checkIn->toDateString().' to '.$checkOut->toDateString().') has been confirmed.',
            'success',
        );

        return redirect()
            ->route('portal.reservations')
            ->with('success', 'Your reservation has been booked. We look forward to hosting you.');
    }

    public function reservations(Request $request): Response
    {
        $guest = $request->user()->guest;

        $reservations = $guest
            ? $this->reservationsQuery($guest)
                ->with(['room:id,room_number,room_type_id'])
                ->latest('check_in')
                ->paginate(10)
                ->through(fn (Reservation $reservation) => $this->serializeReservation($reservation))
                ->withQueryString()
            : null;

        return Inertia::render('Portal/Reservations', [
            'guest' => $guest?->only(['id', 'first_name', 'last_name']),
            'reservations' => $reservations,
        ]);
    }

    public function bills(Request $request): Response
    {
        $user = $request->user();
        $guest = $user->guest;

        $invoices = $guest
            ? $this->invoicesQuery($guest)
                ->with(['payments:id,invoice_id,payment_number,amount,method,status,paid_on'])
                ->latest('issued_on')
                ->paginate(10)
                ->withQueryString()
            : null;

        $payments = $guest
            ? Payment::query()
                ->withoutGlobalScope('branch')
                ->where('guest_id', $guest->id)
                ->with('invoice:id,invoice_number')
                ->latest('paid_on')
                ->paginate(10)
                ->withQueryString()
            : null;

        return Inertia::render('Portal/Bills', [
            'guest' => $guest?->only(['id', 'first_name', 'last_name']),
            'invoices' => $invoices,
            'payments' => $payments,
        ]);
    }

    public function profile(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Portal/Profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'user_type_label' => $user->type_label,
            ],
            'guest' => $user->guest?->only([
                'id', 'first_name', 'last_name', 'email', 'phone', 'address', 'nationality',
            ]),
        ]);
    }

    public function readNotification(Request $request, Notification $notification): RedirectResponse
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $notification->update(['status' => 'read']);

        return back();
    }

    public function readAllNotifications(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        return back();
    }

    private function ordersQuery(\App\Models\User $user)
    {
        return FoodOrder::query()->where(function ($query) use ($user) {
            $query->when(
                $user->guest_id,
                fn ($q) => $q->orWhere('guest_id', $user->guest_id),
            )->orWhere('created_by', $user->id);
        });
    }

    private function reservationsQuery(Guest $guest)
    {
        return Reservation::query()
            ->withoutGlobalScope('branch')
            ->where('guest_id', $guest->id);
    }

    private function invoicesQuery(Guest $guest)
    {
        return Invoice::query()
            ->withoutGlobalScope('branch')
            ->where('guest_id', $guest->id);
    }

    private function nextReservationNumber(Room $room): string
    {
        $branchCode = $room->branch?->code ?? ContextualBranch::current()?->code ?? 'HQ';
        $count = Reservation::withTrashed()->count() + 1;

        return $branchCode.'-RSV-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'reservation_number' => $reservation->reservation_number,
            'check_in' => $reservation->check_in,
            'check_out' => $reservation->check_out,
            'status' => $reservation->status,
            'status_label' => $reservation->status_label,
            'total_amount' => $reservation->total_amount,
            'room' => $reservation->room
                ? ['id' => $reservation->room->id, 'room_number' => $reservation->room->room_number]
                : null,
        ];
    }
}

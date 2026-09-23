<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Support\ContextualBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $invoices = Invoice::query()
            ->with(['reservation:id,reservation_number'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('invoice_number', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('reservation', fn ($x) => $x->where('reservation_number', 'like', '%'.$request->string('search').'%')),
                ),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->invoice_number,
                    'guest' => $invoice->guest?->full_name,
                    'reservation' => $invoice->reservation?->reservation_number,
                    'issued_on' => $invoice->issued_on->toDateString(),
                    'due_on' => $invoice->due_on?->toDateString(),
                    'total' => $invoice->total,
                    'paid' => $invoice->amount_paid,
                    'balance' => $invoice->balance_due,
                    'status' => $invoice->status->value,
                    'status_label' => $invoice->status->label(),
                    'badge_class' => $invoice->status->badgeClass(),
                ],
            );

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('Invoices/Create', [
            'guests' => $this->guestOptions(),
            'reservations' => $this->reservationOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()?->id;
        $data['amount_paid'] = $data['amount_paid'] ?? 0;

        Invoice::create($data);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    public function edit(Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        return Inertia::render('Invoices/Edit', [
            'invoice' => [
                'id' => $invoice->id,
                'branch_id' => $invoice->branch_id,
                'reservation_id' => $invoice->reservation_id,
                'guest_id' => $invoice->guest_id,
                'invoice_number' => $invoice->invoice_number,
                'issued_on' => $invoice->issued_on->toDateString(),
                'due_on' => $invoice->due_on?->toDateString(),
                'subtotal' => $invoice->subtotal,
                'tax' => $invoice->tax,
                'discount' => $invoice->discount,
                'total' => $invoice->total,
                'notes' => $invoice->notes,
                'status' => $invoice->status->value,
            ],
            'reservations' => $this->reservationOptions(),
            'guests' => $this->guestOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice->update($request->validated());

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * @return array<int, array{id: int, reservation_number: string}>
     */
    private function reservationOptions(): array
    {
        return Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->orderByDesc('check_in')
            ->get(['id', 'reservation_number'])
            ->map(fn (Reservation $res) => $res->only(['id', 'reservation_number']))
            ->all();
    }

    /**
     * @return array<int, array{id: int, full_name: string}>
     */
    private function guestOptions(): array
    {
        return \App\Models\Guest::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (\App\Models\Guest $guest) => $guest->only(['id', 'full_name']))
            ->all();
    }
}

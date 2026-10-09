<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Events\PaymentReceived;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\InvoiceService;
use App\Support\ContextualBranch;
use App\Support\GuestNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $branchId = $request->integer('branch_id', ContextualBranch::id() ?? 0);

        $payments = Payment::query()
            ->with(['reservation:id,reservation_number', 'guest:id,first_name,last_name'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')),
            )
            ->when(
                $request->filled('method'),
                fn ($query) => $query->where('method', $request->string('method')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('reference', 'like', '%'.$request->string('search').'%')
                        ->orWhere('payment_number', 'like', '%'.$request->string('search').'%'),
                ),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(
                fn (Payment $payment) => [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'reference' => $payment->reference,
                    'guest' => $payment->guest?->full_name,
                    'reservation' => $payment->reservation?->reservation_number,
                    'method' => $payment->method->value,
                    'method_label' => $payment->method->label(),
                    'method_badge' => $payment->method->badgeClass(),
                    'amount' => $payment->amount,
                    'paid_on' => $payment->paid_on?->toDateString(),
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'status_badge' => $payment->status->badgeClass(),
                ],
            );

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status', 'method', 'branch_id']),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Payment::class);

        return Inertia::render('Payments/Create', [
            'guests' => $this->guestOptions(),
            'reservations' => $this->reservationOptions(),
            'invoices' => $this->invoiceOptions(),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validated();
        $data['received_by'] = $request->user()?->id;

        $payment = DB::transaction(function () use ($data) {
            $payment = Payment::create($data);

            $this->syncBalances($payment);
            PaymentReceived::dispatch($payment, $payment->branch_id);

            return $payment;
        });

        if (($data['status'] ?? null) === 'completed') {
            GuestNotifier::notify(
                $payment->guest,
                'Payment received',
                'We received your payment of ₦'.number_format((float) $payment->amount, 2)
                    .' (ref '.$payment->payment_number.'). Thank you.',
                'success',
            );
        }

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function edit(Payment $payment): Response
    {
        $this->authorize('update', $payment);

        return Inertia::render('Payments/Edit', [
            'payment' => [
                'id' => $payment->id,
                'branch_id' => $payment->branch_id,
                'guest_id' => $payment->guest_id,
                'reservation_id' => $payment->reservation_id,
                'invoice_id' => $payment->invoice_id,
                'payment_number' => $payment->payment_number,
                'reference' => $payment->reference,
                'method' => $payment->method->value,
                'amount' => $payment->amount,
                'paid_on' => $payment->paid_on?->toDateString(),
                'notes' => $payment->notes,
                'status' => $payment->status->value,
            ],
            'guests' => $this->guestOptions(),
            'reservations' => $this->reservationOptions(),
            'invoices' => $this->invoiceOptions($payment->invoice_id),
            'activeBranch' => ContextualBranch::resolve()?->only(['id', 'name', 'code']),
        ]);
    }

    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        DB::transaction(function () use ($request, $payment) {
            $payment->update($request->validated());

            $this->syncBalances($payment);
            PaymentReceived::dispatch($payment, $payment->branch_id);
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        DB::transaction(function () use ($payment) {
            $payment->delete();

            // Recompute after removal (soft-deleted payments drop out of the
            // completed-payments sums).
            $this->syncBalances($payment);
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }

    /**
     * Keep invoice.amount_paid + status and reservation.balance_due in step
     * with the set of completed payments.
     */
    private function syncBalances(Payment $payment): void
    {
        if ($payment->invoice_id) {
            $invoice = Invoice::find($payment->invoice_id);

            if ($invoice) {
                app(InvoiceService::class)->syncPaymentStatus($invoice);
            }
        }

        if ($payment->reservation_id) {
            $reservation = Reservation::withoutGlobalScope('branch')
                ->find($payment->reservation_id);

            if ($reservation) {
                $paid = (float) $reservation->payments()
                    ->where('status', PaymentStatus::Completed->value)
                    ->sum('amount');

                $reservation->update([
                    'balance_due' => max(0, round((float) $reservation->total_amount - $paid, 2)),
                ]);
            }
        }
    }

    /**
     * @return array<int, array{id: int, reservation_number: string}>
     */
    private function reservationOptions(): array
    {
        return Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'extended'])
            ->orderByDesc('check_in')
            ->get(['id', 'reservation_number'])
            ->map(fn (Reservation $res) => $res->only(['id', 'reservation_number']))
            ->all();
    }

    /**
     * Unsettled, non-void invoices for the active branch (plus the invoice
     * already linked to the payment being edited, so it stays selectable).
     *
     * @return array<int, array{id: int, invoice_number: string}>
     */
    private function invoiceOptions(?int $includeId = null): array
    {
        $branchId = ContextualBranch::id();

        return Invoice::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where(fn ($query) => $query
                ->whereIn('status', ['draft', 'sent', 'partially_paid', 'overdue'])
                ->when($includeId, fn ($q) => $q->orWhere('id', $includeId)))
            ->orderByDesc('issued_on')
            ->get(['id', 'invoice_number'])
            ->map(fn (Invoice $invoice) => $invoice->only(['id', 'invoice_number']))
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

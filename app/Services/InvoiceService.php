<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate an invoice for a reservation.
     */
    public function generate(Reservation $reservation, array $lineItems = []): Invoice
    {
        return DB::transaction(function () use ($reservation, $lineItems) {
            $subtotal = collect($lineItems)->sum('amount');
            $taxRate = 0.075; // 7.5% default tax
            $tax = round($subtotal * $taxRate, 2);
            $discount = 0;

            $invoice = Invoice::create([
                'branch_id' => $reservation->branch_id,
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'invoice_number' => $this->nextInvoiceNumber($reservation->branch_id),
                'issued_on' => now()->toDateString(),
                'due_on' => now()->addDays(30)->toDateString(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $subtotal + $tax - $discount,
                'amount_paid' => 0,
                'status' => 'draft',
                'notes' => null,
                'line_items' => $lineItems,
            ]);

            return $invoice;
        });
    }

    /**
     * Update payment status on an invoice based on payments received.
     */
    public function syncPaymentStatus(Invoice $invoice): void
    {
        $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');

        $status = match (true) {
            $totalPaid <= 0 => 'sent',
            $totalPaid >= $invoice->total => 'paid',
            default => 'partially_paid',
        };

        $invoice->update([
            'amount_paid' => $totalPaid,
            'status' => $status,
        ]);
    }

    private function nextInvoiceNumber(int $branchId): string
    {
        $branchCode = \App\Support\ContextualBranch::current()?->code ?? 'HQ';
        $count = Invoice::where('branch_id', $branchId)->count() + 1;

        return $branchCode.'-INV-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

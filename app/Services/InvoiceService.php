<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reservation;
use App\Support\ContextualBranch;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate an invoice for a reservation.
     *
     * Tax defaults to the configured settings tax_rate (percentage) unless a
     * caller supplies its own rate.
     */
    public function generate(Reservation $reservation, array $lineItems = [], ?float $taxRate = null, ?int $createdBy = null): Invoice
    {
        $taxRate ??= $this->taxRate();

        return DB::transaction(function () use ($reservation, $lineItems, $taxRate, $createdBy) {
            $subtotal = collect($lineItems)->sum('amount');
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
                'created_by' => $createdBy,
            ]);

            return $invoice;
        });
    }

    /**
     * Final bill issued at checkout: totals reconcile with the reservation
     * (subtotal = stay charges, no tax markup) and payments already received
     * are reflected in amount_paid + status.
     */
    public function generateFinalInvoice(Reservation $reservation, float $totalPaid, ?int $createdBy = null): Invoice
    {
        $subtotal = round((float) $reservation->total_amount, 2);

        $status = match (true) {
            $totalPaid >= $subtotal => 'paid',
            $totalPaid > 0 => 'partially_paid',
            default => 'sent',
        };

        return Invoice::create([
            'branch_id' => $reservation->branch_id,
            'reservation_id' => $reservation->id,
            'guest_id' => $reservation->guest_id,
            'invoice_number' => $this->nextInvoiceNumber($reservation->branch_id),
            'issued_on' => now()->toDateString(),
            'due_on' => now()->toDateString(),
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'amount_paid' => round($totalPaid, 2),
            'status' => $status,
            'notes' => 'Final bill for reservation '.$reservation->reservation_number,
            'line_items' => [
                [
                    'description' => 'Room charges '.$reservation->reservation_number
                        .' ('.$reservation->check_in->toDateString().' to '.$reservation->check_out->toDateString().')',
                    'amount' => $subtotal,
                ],
            ],
            'created_by' => $createdBy,
        ]);
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
        $branchCode = ContextualBranch::current()?->code ?? 'HQ';
        $count = Invoice::withTrashed()->where('branch_id', $branchId)->count() + 1;

        return $branchCode.'-INV-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Configured tax rate as a decimal fraction (settings store a percentage).
     */
    private function taxRate(): float
    {
        return ((float) Settings::get('tax_rate', 0)) / 100;
    }
}

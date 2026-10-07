<?php

namespace App\Http\Requests;

use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'invoice_number' => [
                'required', 'string', 'max:30',
                Rule::unique('invoices')->ignore($this->route('invoice')),
            ],
            'issued_on' => ['required', 'date'],
            'due_on' => ['required', 'date', 'after_or_equal:issued_on'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'tax' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'discount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'total' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'amount_paid' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([
                'draft', 'sent', 'partially_paid', 'paid', 'overdue', 'void',
            ])],
        ];
    }

    public function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->has('branch_id')) {
            $merge['branch_id'] = ContextualBranch::id();
        }

        if (! $this->has('invoice_number')) {
            $count = \App\Models\Invoice::withTrashed()->count() + 1;
            $branchCode = ContextualBranch::current()?->code ?? 'HQ';
            $merge['invoice_number'] = $branchCode.'-INV-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
        }

        if (! $this->has('amount_paid')) {
            $merge['amount_paid'] = 0;
        }

        if ($this->filled('subtotal') && ! $this->has('total')) {
            $subtotal = (float) $this->input('subtotal', 0);
            $tax = (float) $this->input('tax', 0);
            $discount = (float) $this->input('discount', 0);
            $merge['total'] = max(0, $subtotal + $tax - $discount);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}

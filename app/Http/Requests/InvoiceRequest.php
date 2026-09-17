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
        $branchId = $this->input('branch_id', ContextualBranch::id());

        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'invoice_number' => ['required', 'string', 'max:40', Rule::unique('invoices')],
            'issued_on' => ['required', 'date'],
            'due_on' => ['required', 'date', 'after_or_equal:issued_on'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'tax' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'discount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'total' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([
                'draft', 'sent', 'partially_paid', 'paid', 'overdue', 'void',
            ])],
        ];
    }
}

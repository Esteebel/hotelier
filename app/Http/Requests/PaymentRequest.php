<?php

namespace App\Http\Requests;

use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'payment_reference' => ['required', 'string', 'max:60', Rule::unique('payments')],
            'method' => ['required', Rule::in([
                'cash', 'card', 'bank_transfer', 'mobile_money', 'cheque', 'credit_account',
            ])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'paid_on' => ['required', 'nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['pending', 'completed', 'failed', 'refunded', 'void'])],
        ];
    }
}

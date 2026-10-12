<?php

namespace App\Http\Requests;

use App\Models\Payment;
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
        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'payment_number' => [
                'required', 'string', 'max:30',
                Rule::unique('payments')->ignore($this->route('payment')),
            ],
            'reference' => ['nullable', 'string', 'max:60'],
            'method' => ['required', Rule::in([
                'cash', 'card', 'bank_transfer', 'mobile_money', 'cheque', 'credit_account',
            ])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'paid_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['pending', 'completed', 'failed', 'refunded', 'void'])],
        ];
    }

    public function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->has('branch_id')) {
            $merge['branch_id'] = ContextualBranch::id();
        }

        if (! $this->has('payment_number')) {
            $count = Payment::withTrashed()->count() + 1;
            $branchCode = ContextualBranch::current()?->code ?? 'HQ';
            $merge['payment_number'] = $branchCode.'-PAY-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}

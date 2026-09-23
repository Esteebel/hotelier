<?php

namespace App\Http\Requests;

use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'reservation_number' => ['required', 'string', 'max:30', Rule::unique('reservations')],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'children' => ['required', 'integer', 'min:0', 'max:50'],
            'rate_plan' => ['nullable', 'string', 'max:40'],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'balance_due' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([
                'confirmed', 'checked_in', 'extended', 'checked_out', 'cancelled', 'no_show',
            ])],
        ];
    }

    public function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->has('branch_id')) {
            $merge['branch_id'] = ContextualBranch::id();
        }

        if (! $this->has('reservation_number')) {
            $count = \App\Models\Reservation::withTrashed()->count() + 1;
            $branchCode = ContextualBranch::current()?->code ?? 'HQ';
            $merge['reservation_number'] = $branchCode.'-RSV-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
        }

        if ($this->filled('notes') && ! $this->has('special_requests')) {
            $merge['special_requests'] = $this->input('notes');
        }

        if ($this->filled('total_amount') && ! $this->has('balance_due')) {
            $merge['balance_due'] = $this->input('total_amount');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}

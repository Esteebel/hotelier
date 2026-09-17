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
        $branchId = $this->input('branch_id', ContextualBranch::id());

        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'reservation_code' => ['required', 'string', 'max:40', Rule::unique('reservations')],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'children' => ['required', 'integer', 'min:0', 'max:50'],
            'rate_plan' => ['nullable', 'string', 'max:100'],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([
                'confirmed', 'checked_in', 'extended', 'checked_out', 'cancelled', 'no_show',
            ])],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoomTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo(
            \App\Enums\PermissionName::ManageTransfers->value,
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'from_room_id' => ['required', 'integer', 'exists:rooms,id'],
            'to_room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
                'different:from_room_id',
            ],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'transfer_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['pending', 'approved', 'completed', 'cancelled'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $from = \App\Models\Room::find($this->input('from_room_id'));
            $to = \App\Models\Room::find($this->input('to_room_id'));

            if ($from && $to && (int) $from->branch_id !== (int) $to->branch_id) {
                $validator->errors()->add(
                    'to_room_id',
                    'The destination room must belong to the same branch as the source room.',
                );
            }
        });
    }
}

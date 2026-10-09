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
            \App\Enums\PermissionName::ManageRoomTransfers->value,
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
            $from = \App\Models\Room::withoutGlobalScope('organization')
                ->find($this->input('from_room_id'));
            $to = \App\Models\Room::withoutGlobalScope('organization')
                ->find($this->input('to_room_id'));

            if ($from && $to && (int) $from->branch_id !== (int) $to->branch_id) {
                $validator->errors()->add(
                    'to_room_id',
                    'The destination room must belong to the same branch as the source room.',
                );
            }

            $activeBranch = \App\Support\ContextualBranch::id();
            if ($from && $activeBranch && (int) $from->branch_id !== (int) $activeBranch) {
                $validator->errors()->add(
                    'from_room_id',
                    'The source room must belong to the active branch.',
                );
            }

            if ($to && in_array($to->status->value, [
                \App\Enums\RoomStatus::Occupied->value,
                \App\Enums\RoomStatus::Reserved->value,
                \App\Enums\RoomStatus::Maintenance->value,
                \App\Enums\RoomStatus::OutOfService->value,
            ], true)) {
                $validator->errors()->add(
                    'to_room_id',
                    'The destination room is not available for transfer.',
                );
            }

            if ($this->filled('reservation_id')) {
                $reservation = \App\Models\Reservation::withoutGlobalScope('branch')
                    ->find($this->input('reservation_id'));

                if ($reservation && ! in_array($reservation->status->value, [
                    \App\Enums\ReservationStatus::CheckedIn->value,
                    \App\Enums\ReservationStatus::Extended->value,
                ], true)) {
                    $validator->errors()->add(
                        'reservation_id',
                        'Only guests who are currently checked in can be transferred.',
                    );
                }

                if ($reservation && $this->filled('from_room_id')
                    && (int) $reservation->room_id !== (int) $this->input('from_room_id')
                ) {
                    $validator->errors()->add(
                        'reservation_id',
                        'The reservation must be assigned to the source room.',
                    );
                }
            }
        });
    }
}

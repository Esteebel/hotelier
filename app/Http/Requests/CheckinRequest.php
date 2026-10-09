<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Checkin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageCheckins->value) ?? false;
    }

    public function rules(): array
    {
        $checkin = $this->route('checkin');

        return [
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checkin_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('checkins', 'checkin_number')->ignore($checkin?->id),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('checkin_number')) {
            $this->merge([
                'checkin_number' => Checkin::nextCheckinNumber(),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'reservation_id.exists' => 'The selected reservation is not on file.',
            'room_id.exists' => 'The selected room is not on file.',
            'guest_id.exists' => 'The selected guest is not on file.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Coherence guards only apply when creating a check-in; edits of
            // an existing check-in run against an already checked-in stay.
            if (! $this->isMethod('post')) {
                return;
            }

            $reservation = \App\Models\Reservation::withoutGlobalScope('branch')
                ->find($this->input('reservation_id'));

            if (! $reservation) {
                if ($this->filled('reservation_id')) {
                    $validator->errors()->add('reservation_id', 'The selected reservation is not on file.');
                }

                return;
            }

            if ($reservation->status->value !== \App\Enums\ReservationStatus::Confirmed->value) {
                $validator->errors()->add(
                    'reservation_id',
                    'Only confirmed reservations can be checked in.',
                );
            }

            if ($this->filled('guest_id') && (int) $reservation->guest_id !== (int) $this->input('guest_id')) {
                $validator->errors()->add('guest_id', 'The guest must match the selected reservation.');
            }

            if ($this->filled('room_id')) {
                $room = \App\Models\Room::withoutGlobalScope('organization')
                    ->find($this->input('room_id'));

                if (! $room) {
                    $validator->errors()->add('room_id', 'The selected room is not on file.');
                } elseif ((int) $room->branch_id !== (int) $reservation->branch_id) {
                    $validator->errors()->add(
                        'room_id',
                        'The room must belong to the same branch as the reservation.',
                    );
                } elseif ($reservation->room_id !== null && (int) $reservation->room_id !== (int) $room->id) {
                    $validator->errors()->add(
                        'room_id',
                        'The room must match the room assigned to the reservation.',
                    );
                } elseif (in_array($room->status->value, [
                    \App\Enums\RoomStatus::Maintenance->value,
                    \App\Enums\RoomStatus::OutOfService->value,
                ], true)) {
                    $validator->errors()->add(
                        'room_id',
                        'The room is not available for check-in (maintenance/out of service).',
                    );
                }
            }
        });
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator->errors())->withInput(),
        );
    }
}

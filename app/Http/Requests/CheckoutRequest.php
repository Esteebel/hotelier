<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Checkout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageCheckouts->value) ?? false;
    }

    public function rules(): array
    {
        $checkout = $this->route('checkout');

        return [
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checkout_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('checkouts', 'checkout_number')->ignore($checkout?->id),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('checkout_number')) {
            $this->merge([
                'checkout_number' => Checkout::nextCheckoutNumber(),
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

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            // Coherence guards only apply when creating a check-out; edits of
            // an existing check-out run against an already checked-out stay.
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

            if (! in_array($reservation->status->value, [
                \App\Enums\ReservationStatus::CheckedIn->value,
                \App\Enums\ReservationStatus::Extended->value,
            ], true)) {
                $validator->errors()->add(
                    'reservation_id',
                    'Only guests who are checked in can be checked out.',
                );
            }

            if ($this->filled('guest_id') && (int) $reservation->guest_id !== (int) $this->input('guest_id')) {
                $validator->errors()->add('guest_id', 'The guest must match the selected reservation.');
            }

            if ($this->filled('room_id') && $reservation->room_id !== null
                && (int) $reservation->room_id !== (int) $this->input('room_id')
            ) {
                $validator->errors()->add(
                    'room_id',
                    'The room must match the room assigned to the reservation.',
                );
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

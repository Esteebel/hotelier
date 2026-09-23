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

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator->errors())->withInput(),
        );
    }
}

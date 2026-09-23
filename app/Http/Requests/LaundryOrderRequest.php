<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\LaundryOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class LaundryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageLaundry->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'items' => ['nullable', 'array'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('order_number')) {
            $this->merge(['order_number' => LaundryOrder::nextOrderNumber()]);
        }

        if (is_string($this->input('items'))) {
            $decoded = json_decode($this->input('items'), true);
            $this->merge([
                'items' => is_array($decoded) ? $decoded : [],
            ]);
        }

        if (! $this->has('status')) {
            $this->merge(['status' => 'pending']);
        }
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(back()->withErrors($validator->errors())->withInput());
    }
}

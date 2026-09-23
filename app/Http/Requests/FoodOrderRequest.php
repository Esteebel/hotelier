<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\FoodOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FoodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageRestaurantOrders->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'order_type' => ['required', Rule::in(['dine_in', 'takeout', 'delivery'])],
            'items' => ['nullable', 'array'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('order_number')) {
            $this->merge(['order_number' => FoodOrder::nextOrderNumber()]);
        }

        if (is_string($this->input('items'))) {
            $decoded = json_decode($this->input('items'), true);
            $this->merge([
                'items' => is_array($decoded) ? $decoded : [],
            ]);
        }
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(back()->withErrors($validator->errors())->withInput());
    }
}

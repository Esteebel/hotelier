<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HotelBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'organization_id' => ['required', 'integer', 'exists:hotel_organizations,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('hotel_branches', 'code')->ignore($branch),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'currency' => ['required', Rule::in(['NGN', 'USD', 'GBP', 'EUR'])],
            'star_rating' => ['required', 'integer', 'between:1,7'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
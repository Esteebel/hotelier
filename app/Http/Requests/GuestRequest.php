<?php

namespace App\Http\Requests;

use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guest = $this->route('guest');
        $branchId = $this->input('branch_id', ContextualBranch::id());

        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('guests', 'email')->where('branch_id', $branchId)->ignore($guest),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'id_number' => ['nullable', 'string', 'max:60'],
            'id_type' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
            'company' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_vip' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'former', 'blacklisted', 'archived'])],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('branch_id')) {
            $this->merge([
                'branch_id' => ContextualBranch::id(),
            ]);
        }

        if (! $this->has('status')) {
            $this->merge([
                'status' => 'active',
            ]);
        }
    }
}

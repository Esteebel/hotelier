<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageStaff->value) ?? false;
    }

    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'position' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'on_leave'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('staff_number')) {
            $this->merge([
                'staff_number' => Staff::nextStaffNumber(),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'The staff member first name is required.',
            'last_name.required' => 'The staff member last name is required.',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator->errors())->withInput(),
        );
    }
}

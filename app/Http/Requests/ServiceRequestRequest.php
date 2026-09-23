<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageServices->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'service_type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('service_number')) {
            $this->merge(['service_number' => ServiceRequest::nextServiceNumber()]);
        }
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(back()->withErrors($validator->errors())->withInput());
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ViewAuditLogs->value) ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator->errors())->withInput(),
        );
    }
}

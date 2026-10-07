<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $actor = $this->user();

        if ($actor?->isOrgScoped()) {
            $this->merge(['organization_id' => $actor->organization_id]);
        }

        if ($this->filled('password')) {
            return;
        }

        $default = (string) config('app.default_user_password');

        $this->merge([
            'password' => $default,
            'password_confirmation' => $default,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'user_type' => ['sometimes', Rule::enum(UserType::class)],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'organization_id' => [
                'nullable',
                'integer',
                'exists:hotel_organizations,id',
                Rule::requiredIf(fn () => in_array('hotel-super-admin', (array) $this->input('roles', []), true)),
            ],
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:hotel_branches,id'],
        ];
    }
}
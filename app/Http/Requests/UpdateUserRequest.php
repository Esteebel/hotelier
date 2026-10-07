<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    protected function prepareForValidation(): void
    {
        $actor = $this->user();

        if ($actor?->isOrgScoped()) {
            $this->merge(['organization_id' => $actor->organization_id]);
        }
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'user_type' => ['sometimes', Rule::enum(UserType::class)],
            'guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'organization_id' => [
                'nullable',
                'integer',
                'exists:hotel_organizations,id',
                Rule::requiredIf(function () {
                    return in_array('hotel-super-admin', (array) $this->input('roles', []), true)
                        && ! ($this->filled('organization_id') || $user->organization_id);
                }),
            ],
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:hotel_branches,id'],
        ];
    }
}
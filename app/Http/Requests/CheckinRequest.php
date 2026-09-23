<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Checkin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageCheckins->value) ?? false;
    }

    public function rules(): array
    {
        $checkin = $this->route('checkin');

        return [
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checkin_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('checkins', 'checkin_number')->ignore($checkin?->id),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('checkin_number')) {
            $this->merge([
                'checkin_number' => Checkin::nextCheckinNumber(),
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

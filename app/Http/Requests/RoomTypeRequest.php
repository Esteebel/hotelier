<?php

namespace App\Http\Requests;

use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomType = $this->route('roomType');
        $branchId = $this->input('branch_id', ContextualBranch::id());

        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('room_types', 'name')->where('branch_id', $branchId)->ignore($roomType),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'capacity' => ['required', 'integer', 'between:1,50'],
            'number_of_beds' => ['required', 'integer', 'between:1,10'],
            'bed_configuration' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
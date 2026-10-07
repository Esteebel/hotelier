<?php

namespace App\Http\Requests;

use App\Models\Room;
use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('branch_id')) {
            return;
        }

        $room = $this->route('room');
        $branchId = $room instanceof Room
            ? $room->branch_id
            : ContextualBranch::id();

        if ($branchId !== null) {
            $this->merge(['branch_id' => $branchId]);
        }
    }

    public function rules(): array
    {
        $room = $this->route('room');
        $branchId = $this->input('branch_id', ContextualBranch::id());

        return [
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'room_number' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms', 'room_number')->where('branch_id', $branchId)->ignore($room),
            ],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'between:1,50'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'status' => ['required', Rule::in([
                'available', 'occupied', 'reserved', 'dirty',
                'cleaning', 'maintenance', 'out_of_service',
            ])],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
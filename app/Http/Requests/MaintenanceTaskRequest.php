<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\MaintenanceTask;
use App\Support\ContextualBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MaintenanceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageMaintenance->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'task_number' => ['required', 'string', 'max:30'],
            'branch_id' => ['required', 'integer', 'exists:hotel_branches,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'task_type' => ['required', Rule::in([
                'repair', 'plumbing', 'electrical', 'carpentry', 'painting', 'general', 'maintenance',
            ])],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['pending', 'in-progress', 'on-hold', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->has('task_number')) {
            $merge['task_number'] = MaintenanceTask::nextTaskNumber();
        }

        if (! $this->has('branch_id')) {
            $merge['branch_id'] = ContextualBranch::id();
        }

        if (! $this->has('status')) {
            $merge['status'] = 'pending';
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function messages(): array
    {
        return [
            'room_id.exists' => 'The selected room is not on file.',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator->errors())->withInput(),
        );
    }
}

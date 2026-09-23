<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageExpenses->value) ?? false;
    }

    public function rules(): array
    {
        $expense = $this->route('expense');

        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:80'],
            'expense_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->has('expense_number')) {
            $this->merge([
                'expense_number' => Expense::nextExpenseNumber(),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'The expense amount must be at least 0.01.',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator->errors())->withInput(),
        );
    }
}

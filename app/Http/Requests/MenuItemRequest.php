<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            ?->hasPermissionTo(PermissionName::ManageMenuItems->value) ?? false;
    }

    public function rules(): array
    {
        $menuItem = $this->route('menuItem');

        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(MenuItem::categories())],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'is_available' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }
}

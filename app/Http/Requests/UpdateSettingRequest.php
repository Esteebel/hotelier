<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.brand_name' => ['required', 'string', 'max:255'],
            'settings.default_currency' => ['required', 'string', 'size:3'],
            'settings.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.check_in_time' => ['required', 'date_format:H:i'],
            'settings.check_out_time' => ['required', 'date_format:H:i'],
            'settings.timezone' => ['required', 'string', 'max:255'],
            'settings.default_page_size' => ['required', 'integer', 'between:1,100'],
        ];
    }
}

<?php

namespace App\Support;

use App\Models\HotelSetting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    /**
     * Defaults merged under any saved values. `tax_rate` is a percentage
     * applied by InvoiceService when generating invoices from line items.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'brand_name' => 'Hotel Management System',
        'default_currency' => 'NGN',
        'tax_rate' => 7.5,
        'check_in_time' => '14:00',
        'check_out_time' => '12:00',
        'timezone' => 'Africa/Lagos',
        'default_page_size' => 10,
    ];

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::remember('hotel.settings', now()->addMinutes(5), function () {
            $saved = HotelSetting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (HotelSetting $setting) => [$setting->key => $setting->value]);

            return collect(self::DEFAULTS)
                ->map(fn (mixed $default, string $key) => $saved[$key] ?? $default)
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function forget(): void
    {
        Cache::forget('hotel.settings');
    }
}

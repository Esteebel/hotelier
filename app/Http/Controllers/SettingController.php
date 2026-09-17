<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\HotelSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const DEFAULTS = [
        'brand_name' => 'Hotel Management System',
        'default_currency' => 'NGN',
        'check_in_time' => '14:00',
        'check_out_time' => '12:00',
        'timezone' => 'Africa/Lagos',
        'default_page_size' => 10,
    ];

    public function index(Request $request): Response
    {
        $this->authorizeManage($request);

        $saved = HotelSetting::query()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (HotelSetting $setting) => [$setting->key => $setting->value]);

        $settings = collect(self::DEFAULTS)
            ->map(fn (mixed $default, string $key) => $saved[$key] ?? $default);

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $this->authorizeManage($request);

        foreach ($request->validated()['settings'] as $key => $value) {
            HotelSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    private function authorizeManage(Request $request): void
    {
        if (! $request->user()->hasPermissionTo(PermissionName::ManageSettings->value)) {
            abort(403);
        }
    }
}
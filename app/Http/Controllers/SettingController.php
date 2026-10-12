<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\HotelSetting;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Settings/Index', [
            'settings' => Settings::all(),
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

        Settings::forget();

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

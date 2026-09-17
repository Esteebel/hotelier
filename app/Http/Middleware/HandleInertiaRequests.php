<?php

namespace App\Http\Middleware;

use App\Support\ContextualBranch;
use App\Models\HotelBranch;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => $user?->roles->pluck('name'),
                'permissions' => $user?->getAllPermissions()->pluck('name'),
            ],
            'branches' => $user
                ? $user->branches()
                    ->select('hotel_branches.*')
                    ->orderBy('is_default', 'desc')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (HotelBranch $branch) => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                        'city' => $branch->city,
                        'state' => $branch->state,
                        'status' => $branch->status->value,
                    ])
                : [],
            'activeBranch' => $user ? ContextualBranch::resolve()?->only(['id', 'name', 'code', 'city', 'state']) : null,
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }
}
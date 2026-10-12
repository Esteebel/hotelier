<?php

namespace App\Http\Middleware;

use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\Notification;
use App\Support\ContextualBranch;
use App\Support\ContextualOrganization;
use App\Support\Settings;
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
                'isCustomer' => $user?->isCustomer() ?? false,
            ],
            'branches' => $user
                ? ContextualBranch::visibleBranches($user)
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
            'canFilterOrganizations' => $user !== null
                && ! $user->isCustomer()
                && ! $user->isOrgScoped(),
            'activeOrganizationId' => $user ? ContextualOrganization::id() : null,
            'settings' => Settings::all(),
            'navOrganizations' => $user && ! $user->isCustomer()
                ? ($user->isOrgScoped()
                        ? HotelOrganization::query()->whereKey($user->organization_id)
                        : HotelOrganization::query()->withoutGlobalScope('organization'))
                    ->orderBy('name')
                    ->get()
                    ->map(fn (HotelOrganization $organization) => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                        'code' => $organization->code,
                    ])
                : [],
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
            'portalNotifications' => $user && $user->isCustomer()
                ? Notification::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn (Notification $notification) => [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'status' => $notification->status,
                        'created_at' => $notification->created_at->toDateTimeString(),
                    ])
                : null,
            'portalNotificationsUnread' => $user && $user->isCustomer()
                ? Notification::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'unread')
                    ->count()
                : 0,
        ];
    }
}

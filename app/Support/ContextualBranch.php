<?php

namespace App\Support;

use App\Models\HotelBranch;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Resolves the branch the authenticated user is currently working in.
 * Falls back to the default active branch when no explicit selection exists.
 *
 * Branches are always constrained to the organization the caller is
 * currently viewing (their own organization for hotel admins, the navbar
 * selection for super admins).
 */
class ContextualBranch
{
    public static function resolve(?Request $request = null): ?HotelBranch
    {
        $request ??= request();

        $hasSession = $request->hasSession();
        $branchId = $hasSession
            ? $request->session()->get('active_branch_id')
            : null;
        $organizationId = ContextualOrganization::id($request);

        $query = HotelBranch::query();

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        $branch = $branchId
            ? (clone $query)->find($branchId)
            : null;

        return $branch
            ?? (clone $query)
                ->where('status', 'active')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->first();
    }

    public static function id(?Request $request = null): ?int
    {
        return self::resolve($request)?->getKey();
    }

    public static function current(?Request $request = null): ?HotelBranch
    {
        return self::resolve($request);
    }

    /**
     * Branches a user may select in the branch switcher.
     *
     * - A selected organization (hotel admins are always pinned to theirs):
     *   every branch of that organization.
     * - Super admins on "All organizations": every branch, everywhere.
     * - Other platform users: only their explicitly assigned branches.
     *
     * @return \Illuminate\Database\Eloquent\Builder<HotelBranch>
     */
    public static function visibleBranches(User $user)
    {
        $organizationId = ContextualOrganization::id();

        if ($organizationId !== null) {
            return HotelBranch::query()
                ->where('organization_id', $organizationId);
        }

        if ($user->hasRole('super-admin')) {
            return HotelBranch::query();
        }

        return HotelBranch::query()
            ->whereIn('id', $user->branches()->select('hotel_branches.id'));
    }
}

<?php

namespace App\Support;

use App\Models\HotelBranch;
use Illuminate\Http\Request;

/**
 * Resolves the branch the authenticated user is currently working in.
 * Falls back to the default active branch when no explicit selection exists.
 */
class ContextualBranch
{
    public static function resolve(?Request $request = null): ?HotelBranch
    {
        $request ??= request();

        $branchId = $request->session()->get('active_branch_id');

        $branch = $branchId
            ? HotelBranch::query()->find($branchId)
            : null;

        return $branch
            ?? HotelBranch::query()
                ->where('status', 'active')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->first();
    }

    public static function id(?Request $request = null): ?int
    {
        return self::resolve($request)?->getKey();
    }
}
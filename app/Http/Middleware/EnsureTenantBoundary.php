<?php

namespace App\Http\Middleware;

use App\Models\HotelBranch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks organization-scoped users from writing across tenants by passing
 * foreign organization_id / branch_id values in request payloads.
 *
 * Reads are already protected by the per-model organization global scopes;
 * this guard covers raw `exists:` validation and unscoped write paths.
 */
class EnsureTenantBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isOrgScoped() || $request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->filled('organization_id')
            && (int) $request->input('organization_id') !== (int) $user->organization_id
        ) {
            abort(403, 'You can only manage resources within your own organization.');
        }

        foreach ($this->requestedBranchIds($request) as $branchId) {
            $allowed = HotelBranch::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($branchId)
                ->exists();

            if (! $allowed) {
                abort(403, 'You can only manage resources within your own organization.');
            }
        }

        return $next($request);
    }

    /**
     * @return array<int, int>
     */
    private function requestedBranchIds(Request $request): array
    {
        $ids = [];

        if ($request->filled('branch_id')) {
            $ids[] = (int) $request->input('branch_id');
        }

        $branches = $request->input('branches');

        if (is_array($branches)) {
            foreach ($branches as $branchId) {
                $ids[] = (int) $branchId;
            }
        }

        return array_values(array_filter($ids));
    }
}

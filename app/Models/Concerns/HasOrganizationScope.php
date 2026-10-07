<?php

namespace App\Models\Concerns;

use App\Models\HotelBranch;
use App\Support\ContextualOrganization;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts every query on a branch-owned model to the organization the
 * caller is currently viewing.
 *
 * Hotel admins are pinned to their own organization; super admins default to
 * "All organizations" (NULL = unscoped) and can narrow the panel to one
 * organization via the navbar switcher.
 */
trait HasOrganizationScope
{
    public static function bootHasOrganizationScope(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $organizationId = ContextualOrganization::id();

            if ($organizationId === null) {
                return;
            }

            $builder->whereIn(
                'branch_id',
                HotelBranch::query()
                    ->where('organization_id', $organizationId)
                    ->select('id'),
            );
        });
    }
}

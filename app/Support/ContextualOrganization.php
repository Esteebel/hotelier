<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the organization the admin panel is currently filtered to.
 *
 * - Hotel admins (organization-scoped users) are always pinned to their own
 *   organization, regardless of any session selection.
 * - Platform users (super admins) default to null = "All organizations" and
 *   can narrow every screen to a single organization through the navbar
 *   switcher (session key `active_organization_id`).
 */
class ContextualOrganization
{
    public static function id(?Request $request = null): ?int
    {
        $request ??= request();

        $user = $request->user();

        if ($user?->isOrgScoped()) {
            return $user->organization_id;
        }

        if (! $request->hasSession()) {
            return null;
        }

        $active = $request->session()->get('active_organization_id');

        return $active !== null ? (int) $active : null;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks customer accounts from the staff/admin area.
 */
class EnsureStaffArea
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isCustomer()) {
            return redirect()->route('portal.home');
        }

        return $next($request);
    }
}

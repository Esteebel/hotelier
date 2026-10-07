<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the customer portal to customer accounts.
 */
class EnsureCustomerArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isCustomer()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

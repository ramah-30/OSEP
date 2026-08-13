<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route on a granular permission, e.g. `permission:create_event`. Any
 * one of the listed permissions is enough (OR semantics).
 */
class EnsurePermission
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyPermission($permissions)) {
            return $this->error('You do not have permission to perform this action.', null, 403);
        }

        return $next($request);
    }
}

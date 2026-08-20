<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route to one or more account types, e.g. `role:client` or
 * `role:event_planner,admin`. A matching seeded role name is also accepted so
 * an admin can be granted access without owning the account type.
 */
class EnsureAccountType
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        $allowed = $user
            && (in_array($user->account_type->value, $types, true)
                || collect($types)->contains(fn (string $type) => $user->hasRole($type)));

        if (! $allowed) {
            return $this->error('This area is not available for your account type.', null, 403);
        }

        return $next($request);
    }
}

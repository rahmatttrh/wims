<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !$user->roles()->whereIn('name', $roles)->exists()) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}

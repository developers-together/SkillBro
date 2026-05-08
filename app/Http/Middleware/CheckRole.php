<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $userRole = $request->user()?->role;

        if (! $userRole instanceof UserRole || $userRole->value !== $role) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}

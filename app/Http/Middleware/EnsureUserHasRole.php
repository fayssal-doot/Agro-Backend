<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string ...$roles  Comma-separated roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Admin bypasses all role checks
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Normalize both sides and compare
        $allowedRoles = array_map('strtolower', $roles);
        $userRole = strtolower(str_replace(' ', '_', $user->role));

        if (!in_array($userRole, $allowedRoles, true)) {
            return response()->json(['message' => 'Forbidden - insufficient role'], 403);
        }

        // For sellers: require 'active' status to perform operations
        if (in_array($userRole, ['farmer', 'store_owner'], true) && $user->status !== 'active') {
            return response()->json(['message' => 'Forbidden - account pending approval'], 403);
        }

        return $next($request);
    }
}

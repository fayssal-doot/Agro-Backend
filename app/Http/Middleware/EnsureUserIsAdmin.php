<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Use environment variable for admin email (security best practice)
        $authorizedEmail = env('AUTHORIZED_ADMIN_EMAIL');

        if ($user->role !== 'admin' || ($authorizedEmail && $user->email !== $authorizedEmail)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

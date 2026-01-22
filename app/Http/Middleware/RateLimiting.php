<?php

namespace App\Http\Middleware;

use App\Models\SecurityEvent;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimiting
{
    /**
     * The rate limiter instance.
     */
    protected RateLimiter $limiter;

    /**
     * Create a new middleware instance.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request with rate limiting.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRateLimitKey($request);
        $limit = $this->getEndpointLimit($request);
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            // Log rate limit exceeded event
            SecurityEvent::log(
                SecurityEvent::TYPE_RATE_LIMIT_EXCEEDED,
                SecurityEvent::SEVERITY_MEDIUM,
                $request->user()?->email,
                $request->ip(),
                [
                    'endpoint' => $request->path(),
                    'limit' => $limit,
                ],
                'Rate limit exceeded'
            );

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }

    /**
     * Resolve the rate limit key for the request.
     */
    private function resolveRateLimitKey(Request $request): string
    {
        $identifier = $request->user()?->id ?? $request->ip();

        return 'rate_limit:' . $request->path() . ':' . $identifier;
    }

    /**
     * Get the rate limit for the current endpoint.
     */
    private function getEndpointLimit(Request $request): int
    {
        $path = $request->path();

        // Endpoint-specific limits
        return match (true) {
            str_contains($path, 'auth/login') => 5,           // 5 per minute
            str_contains($path, 'auth/register') => 5,        // 5 per minute
            str_contains($path, 'mfa/verify') => 10,          // 10 per minute
            str_contains($path, 'upload') => 5,               // 5 per minute
            str_contains($path, 'password') => 5,             // 5 per minute
            default => 60,                                     // 60 per minute (default)
        };
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add comprehensive security headers to every response.
     * Implements OWASP recommended security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Strict-Transport-Security: Force HTTPS for 1 year
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-XSS-Protection: Enable XSS filter in browsers (older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Control referrer information sent to other sites
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: Restrict browser features and APIs
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        // Content-Security-Policy: Prevent XSS and injection attacks
        $this->setContentSecurityPolicy($response);

        // Remove server identification headers
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Set Content Security Policy header to prevent XSS attacks.
     */
    private function setContentSecurityPolicy(Response $response): void
    {
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self'; ";
        $csp .= "style-src 'self' 'unsafe-inline'; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "font-src 'self' data:; ";
        $csp .= "connect-src 'self' https://firebaseapp.com https://googleapis.com; ";
        $csp .= "frame-ancestors 'none'; ";
        $csp .= "base-uri 'self'; ";
        $csp .= "form-action 'self'; ";
        $csp .= "upgrade-insecure-requests;";

        $response->headers->set('Content-Security-Policy', $csp);
    }
}

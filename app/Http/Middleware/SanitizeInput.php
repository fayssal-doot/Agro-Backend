<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Sensitive fields that should be redacted from logs and responses.
     */
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'id_token',
        'api_key',
        'secret',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security_number',
        'phone',
        'email',
    ];

    /**
     * Handle an incoming request - sanitize inputs to prevent XSS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize request input to prevent XSS attacks
        $this->sanitizeRequest($request);

        return $next($request);
    }

    /**
     * Recursively sanitize request input.
     */
    private function sanitizeRequest(Request $request): void
    {
        $sanitized = [];

        foreach ($request->all() as $key => $value) {
            if ($this->isSensitiveField($key)) {
                // Don't sanitize sensitive fields, keep them as-is
                $sanitized[$key] = $value;
            } else {
                // Sanitize string values to prevent XSS
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }

        $request->merge($sanitized);
    }

    /**
     * Sanitize a single value recursively.
     */
    private function sanitizeValue($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }

        if (is_string($value)) {
            // Remove null bytes
            $value = str_replace("\0", '', $value);

            // HTML entity encode to prevent XSS
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * Check if a field is sensitive and should not be sanitized.
     */
    private function isSensitiveField(string $field): bool
    {
        $field = strtolower($field);

        foreach ($this->sensitiveFields as $sensitive) {
            if (strpos($field, strtolower($sensitive)) !== false) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use HasFactory;

    /**
     * Event type constants.
     */
    public const TYPE_LOGIN_SUCCESS = 'login_success';
    public const TYPE_LOGIN_FAILED = 'login_failed';
    public const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    public const TYPE_ACCOUNT_LOCKOUT = 'account_lockout';
    public const TYPE_MFA_SETUP = 'mfa_setup';
    public const TYPE_MFA_DISABLED = 'mfa_disabled';
    public const TYPE_ADMIN_ACTION = 'admin_action';
    public const TYPE_PERMISSION_DENIED = 'permission_denied';
    public const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    public const TYPE_DATA_ACCESS = 'data_access';
    public const TYPE_DATA_MODIFICATION = 'data_modification';
    public const TYPE_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';

    /**
     * Severity level constants.
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_type',
        'severity',
        'user_email',
        'client_ip',
        'user_agent',
        'request_path',
        'request_method',
        'metadata',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Log a security event.
     *
     * @param string $eventType
     * @param string $severity
     * @param string|null $userEmail
     * @param string|null $clientIp
     * @param array $metadata
     * @param string|null $description
     * @return self
     */
    public static function log(
        string $eventType,
        string $severity,
        ?string $userEmail = null,
        ?string $clientIp = null,
        array $metadata = [],
        ?string $description = null
    ): self {
        return static::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'user_email' => $userEmail,
            'client_ip' => $clientIp,
            'user_agent' => request()->userAgent(),
            'request_path' => request()->path(),
            'request_method' => request()->method(),
            'metadata' => $metadata,
            'description' => $description,
        ]);
    }

    /**
     * Get events by severity level.
     */
    public static function bySeverity(string $severity)
    {
        return static::where('severity', $severity)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get recent critical events.
     */
    public static function recentCritical(?int $minutes = 60)
    {
        return static::where('severity', static::SEVERITY_CRITICAL)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get events for a specific user.
     */
    public static function forUser(string $email)
    {
        return static::where('user_email', $email)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get events from a specific IP address.
     */
    public static function fromIp(string $ip)
    {
        return static::where('client_ip', $ip)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if there are suspicious activities from an IP.
     */
    public static function isSuspiciousIp(string $ip, int $failedAttemptsThreshold = 5, int $withinMinutes = 15): bool
    {
        $suspiciousCount = static::where('client_ip', $ip)
            ->where('severity', '>=', static::SEVERITY_MEDIUM)
            ->where('created_at', '>=', now()->subMinutes($withinMinutes))
            ->count();

        return $suspiciousCount >= $failedAttemptsThreshold;
    }
}

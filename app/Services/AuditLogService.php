<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Log an authentication event.
     */
    public static function log(
        string $event,
        string $status,
        ?int $userId = null,
        ?string $email = null,
        ?string $failureReason = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditLog {
        $request = $request ?? request();

        return AuditLog::create([
            'user_id' => $userId,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email' => $email,
            'metadata' => $metadata ?? self::getDefaultMetadata($request),
            'status' => $status,
            'failure_reason' => $failureReason,
        ]);
    }

    /**
     * Log a successful login.
     */
    public static function logLoginSuccess(int $userId, string $email, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_LOGIN_SUCCESS,
            AuditLog::STATUS_SUCCESS,
            $userId,
            $email,
            null,
            null,
            $request
        );
    }

    /**
     * Log a failed login attempt.
     */
    public static function logLoginFailed(string $email, string $reason, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_LOGIN_FAILED,
            AuditLog::STATUS_FAILED,
            null,
            $email,
            $reason,
            null,
            $request
        );
    }

    /**
     * Log account lockout.
     */
    public static function logAccountLocked(string $email, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_ACCOUNT_LOCKED,
            AuditLog::STATUS_BLOCKED,
            null,
            $email,
            'Too many failed login attempts',
            null,
            $request
        );
    }

    /**
     * Log successful logout.
     */
    public static function logLogout(int $userId, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_LOGOUT,
            AuditLog::STATUS_SUCCESS,
            $userId,
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log token creation.
     */
    public static function logTokenCreated(int $userId, ?string $tokenName = null, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_TOKEN_CREATED,
            AuditLog::STATUS_SUCCESS,
            $userId,
            null,
            null,
            ['token_name' => $tokenName ?? 'auth_token'],
            $request
        );
    }

    /**
     * Log token refresh.
     */
    public static function logTokenRefreshed(int $userId, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_TOKEN_REFRESHED,
            AuditLog::STATUS_SUCCESS,
            $userId,
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log token revocation.
     */
    public static function logTokenRevoked(int $userId, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_TOKEN_REVOKED,
            AuditLog::STATUS_SUCCESS,
            $userId,
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log user registration.
     */
    public static function logRegister(int $userId, string $email, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_REGISTER,
            AuditLog::STATUS_SUCCESS,
            $userId,
            $email,
            null,
            null,
            $request
        );
    }

    /**
     * Get default metadata from request.
     */
    protected static function getDefaultMetadata(Request $request): array
    {
        return [
            'referer' => $request->header('Referer'),
            'origin' => $request->header('Origin'),
            'accept_language' => $request->header('Accept-Language'),
        ];
    }

    /**
     * Get recent failed login attempts for an email.
     */
    public static function getRecentFailedAttempts(string $email, int $minutes = 15): int
    {
        return AuditLog::where('email', strtolower($email))
            ->where('event', AuditLog::EVENT_LOGIN_FAILED)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get recent failed login attempts from an IP.
     */
    public static function getRecentFailedAttemptsFromIp(string $ip, int $minutes = 15): int
    {
        return AuditLog::where('ip_address', $ip)
            ->where('event', AuditLog::EVENT_LOGIN_FAILED)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Check if IP is suspicious (many failed attempts).
     */
    public static function isIpSuspicious(string $ip, int $threshold = 10, int $minutes = 60): bool
    {
        return self::getRecentFailedAttemptsFromIp($ip, $minutes) >= $threshold;
    }
}

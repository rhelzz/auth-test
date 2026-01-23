<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    // Event types
    public const EVENT_LOGIN_ATTEMPT = 'login_attempt';
    public const EVENT_LOGIN_SUCCESS = 'login_success';
    public const EVENT_LOGIN_FAILED = 'login_failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_TOKEN_CREATED = 'token_created';
    public const EVENT_TOKEN_REFRESHED = 'token_refreshed';
    public const EVENT_TOKEN_REVOKED = 'token_revoked';
    public const EVENT_ACCOUNT_LOCKED = 'account_locked';
    public const EVENT_REGISTER = 'register';

    // Status types
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'user_agent',
        'email',
        'metadata',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this log entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by event type
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by IP address
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}

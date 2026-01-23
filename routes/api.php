<?php

use App\Services\AuditLogService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Fortify handles authentication routes automatically:
// POST /api/register - Register new user
// POST /api/login - Login user

// Public route for token refresh (no auth required, uses refresh_token)
Route::post('/refresh', function (Request $request) {
    $request->validate([
        'refresh_token' => 'required|string|size:64',
    ]);

    $tokens = TokenService::refreshTokens($request->refresh_token, $request);

    if (!$tokens) {
        return response()->json([
            'message' => 'Invalid or expired refresh token',
        ], 401);
    }

    return response()->json([
        'message' => 'Token refreshed successfully',
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'token_type' => $tokens['token_type'],
        'expires_in' => $tokens['expires_in'],
        'refresh_expires_in' => $tokens['refresh_expires_in'],
    ]);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Logout (revoke current token only)
    Route::post('/logout', function (Request $request) {
        $user = $request->user();
        
        // Revoke current token and associated refresh token
        TokenService::revokeCurrentToken($user, $request);
        
        // Log logout
        AuditLogService::logLogout($user->id, $request);
        
        return response()->json([
            'message' => 'Logout successful'
        ]);
    });

    // Logout from all devices (revoke all tokens)
    Route::post('/logout-all', function (Request $request) {
        $user = $request->user();
        
        // Revoke all tokens
        TokenService::revokeAllTokens($user, $request);
        
        // Log logout
        AuditLogService::logLogout($user->id, $request);
        
        return response()->json([
            'message' => 'Logged out from all devices successfully'
        ]);
    });
    
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'created_at'])
        ]);
    });

    // Get user's active sessions (refresh tokens)
    Route::get('/sessions', function (Request $request) {
        $sessions = $request->user()
            ->refreshTokens()
            ->valid()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) use ($request) {
                return [
                    'id' => $token->id,
                    'ip_address' => $token->ip_address,
                    'user_agent' => $token->user_agent,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'is_current' => $token->access_token_id == $request->user()->currentAccessToken()->id,
                ];
            });

        return response()->json([
            'sessions' => $sessions
        ]);
    });

    // Get user's recent audit logs
    Route::get('/audit-logs', function (Request $request) {
        $logs = \App\Models\AuditLog::forUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'event' => $log->event,
                    'status' => $log->status,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at,
                ];
            });

        return response()->json([
            'audit_logs' => $logs
        ]);
    });
});

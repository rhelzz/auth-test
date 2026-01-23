<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;

class TokenService
{
    /**
     * Access token expiration in minutes.
     */
    public const ACCESS_TOKEN_EXPIRATION = 15; // 15 minutes

    /**
     * Refresh token expiration in days.
     */
    public const REFRESH_TOKEN_EXPIRATION_DAYS = 7; // 7 days

    /**
     * Create access and refresh tokens for a user.
     */
    public static function createTokens(User $user, ?Request $request = null): array
    {
        $request = $request ?? request();

        // Create Sanctum access token
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        
        // Extract token ID from plainTextToken (format: "id|token")
        $accessTokenId = explode('|', $accessToken)[0];

        // Generate refresh token
        $plainRefreshToken = RefreshToken::generateToken();
        
        // Store hashed refresh token
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => RefreshToken::hashToken($plainRefreshToken),
            'access_token_id' => $accessTokenId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_EXPIRATION_DAYS),
        ]);

        // Log token creation
        AuditLogService::logTokenCreated($user->id, 'auth_token', $request);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $plainRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_EXPIRATION * 60, // in seconds
            'refresh_expires_in' => self::REFRESH_TOKEN_EXPIRATION_DAYS * 24 * 60 * 60, // in seconds
        ];
    }

    /**
     * Refresh tokens using a valid refresh token.
     */
    public static function refreshTokens(string $plainRefreshToken, ?Request $request = null): ?array
    {
        $request = $request ?? request();
        $hashedToken = RefreshToken::hashToken($plainRefreshToken);

        // Find the refresh token
        $refreshToken = RefreshToken::where('token', $hashedToken)->first();

        if (!$refreshToken || !$refreshToken->isValid()) {
            return null;
        }

        $user = $refreshToken->user;

        // Revoke old refresh token (rotation)
        $refreshToken->revoke();

        // Revoke old access token if exists
        if ($refreshToken->access_token_id) {
            $user->tokens()->where('id', $refreshToken->access_token_id)->delete();
        }

        // Create new tokens
        $newTokens = self::createTokens($user, $request);

        // Log token refresh
        AuditLogService::logTokenRefreshed($user->id, $request);

        return $newTokens;
    }

    /**
     * Revoke all tokens for a user.
     */
    public static function revokeAllTokens(User $user, ?Request $request = null): void
    {
        $request = $request ?? request();

        // Revoke all Sanctum tokens
        $user->tokens()->delete();

        // Revoke all refresh tokens
        RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        // Log token revocation
        AuditLogService::logTokenRevoked($user->id, $request);
    }

    /**
     * Revoke current token only.
     */
    public static function revokeCurrentToken(User $user, ?Request $request = null): void
    {
        $request = $request ?? request();

        // Get current access token ID
        $currentToken = $user->currentAccessToken();
        
        if ($currentToken) {
            $tokenId = $currentToken->id;

            // Revoke associated refresh token
            RefreshToken::where('access_token_id', $tokenId)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            // Delete the current access token
            $currentToken->delete();
        }

        // Log token revocation
        AuditLogService::logTokenRevoked($user->id, $request);
    }

    /**
     * Clean up expired refresh tokens.
     */
    public static function cleanupExpiredTokens(): int
    {
        return RefreshToken::where('expires_at', '<', now())
            ->orWhereNotNull('revoked_at')
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();
    }
}

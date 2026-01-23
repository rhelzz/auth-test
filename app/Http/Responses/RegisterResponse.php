<?php

namespace App\Http\Responses;

use App\Services\AuditLogService;
use App\Services\TokenService;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        
        // Create access + refresh tokens
        $tokens = TokenService::createTokens($user, $request);
        
        // Log registration
        AuditLogService::logRegister($user->id, $user->email, $request);

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_expires_in' => $tokens['refresh_expires_in'],
        ], 201);
    }
}

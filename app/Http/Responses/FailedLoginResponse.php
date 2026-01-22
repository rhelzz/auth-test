<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Contracts\FailedLoginResponse as FailedLoginResponseContract;

class FailedLoginResponse implements FailedLoginResponseContract
{
    public function toResponse($request)
    {
        $email = strtolower(trim($request->email ?? ''));
        $lockoutKey = 'login-lockout:' . $email;
        
        // Check if account is locked
        if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
            $seconds = RateLimiter::availableIn($lockoutKey);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'message' => "Akun dikunci sementara karena terlalu banyak percobaan gagal. Silakan coba lagi dalam {$minutes} menit.",
                'retry_after' => $seconds,
                'locked' => true,
            ], 429);
        }
        
        $remainingAttempts = RateLimiter::remaining($lockoutKey, 5);
        
        // Generic error message - TIDAK mengungkapkan apakah email terdaftar atau tidak
        return response()->json([
            'message' => 'Email atau password salah.',
            'attempts_remaining' => $remainingAttempts,
        ], 401);
    }
}

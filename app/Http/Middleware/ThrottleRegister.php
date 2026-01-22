<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRegister
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to register route
        if (!$request->is('api/register')) {
            return $next($request);
        }

        $key = 'register|' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            
            $timeMessage = '';
            if ($hours > 0) {
                $timeMessage = "{$hours} jam";
                if ($remainingMinutes > 0) {
                    $timeMessage .= " {$remainingMinutes} menit";
                }
            } else {
                $timeMessage = "{$minutes} menit";
            }
            
            return response()->json([
                'message' => "Anda hanya dapat mendaftar 1x setiap 3 jam. Silakan tunggu {$timeMessage} lagi.",
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => 1,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $response = $next($request);
        
        // Only hit rate limiter if registration was successful (201 Created)
        if ($response->getStatusCode() === 201) {
            // 3 hours = 10800 seconds
            RateLimiter::hit($key, 10800);
        }
        
        return $response;
    }
}

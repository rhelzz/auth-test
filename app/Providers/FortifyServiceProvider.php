<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;
use Laravel\Fortify\Fortify;
use App\Http\Responses\LockoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom response contracts
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        
        // Custom lockout response (when account is locked after too many failed attempts)
        $this->app->singleton(LockoutResponseContract::class, LockoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Custom authentication with strict validation and account lockout
        Fortify::authenticateUsing(function (Request $request) {
            // Validate login input strictly - ONLY email and password allowed
            $validator = Validator::make($request->only(['email', 'password']), [
                'email' => [
                    'required',
                    'string',
                    'email:rfc',
                    'max:255',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                ],
            ]);

            if ($validator->fails()) {
                return null;
            }

            // Normalize email
            $email = strtolower(trim($request->email));
            
            // Check if account is locked (5 failed attempts = 15 min lockout)
            $lockoutKey = 'login-lockout:' . $email;
            if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
                return null; // Account is locked
            }

            // Find user
            $user = User::where('email', $email)->first();

            // SECURITY: Always perform hash check to prevent timing attacks
            // Even if user doesn't exist, we hash a dummy password
            $passwordToCheck = $user ? $user->password : '$2y$10$dummyhashtopreventtimingattacks';
            $isValidPassword = Hash::check($request->password, $passwordToCheck);

            // Verify password
            if ($user && $isValidPassword) {
                // Clear failed attempts on successful login
                RateLimiter::clear($lockoutKey);
                return $user;
            }

            // Increment failed attempts (15 min decay)
            RateLimiter::hit($lockoutKey, 900);

            return null;
        });

        // Rate limiting for login - 1 attempt per minute per IP
        RateLimiter::for('login', function (Request $request) {
            $ip = $request->ip();
            
            return Limit::perMinute(1)->by('login|' . $ip)->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan login. Silakan tunggu 1 menit.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            });
        });

        // Rate limiting for registration - 1 attempt per 3 hours per IP
        RateLimiter::for('register', function (Request $request) {
            $ip = $request->ip();
            
            // 3 hours = 180 minutes
            return Limit::perMinutes(180, 1)->by('register|' . $ip)->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Anda hanya dapat mendaftar 1x setiap 3 jam. Silakan tunggu.',
                    'retry_after' => $headers['Retry-After'] ?? 10800,
                ], 429, $headers);
            });
        });

        // Rate limiting for two-factor
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

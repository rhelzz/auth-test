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
use Laravel\Fortify\Fortify;

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

        // Custom authentication with strict validation
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

            // Find user
            $user = User::where('email', $email)->first();

            // Verify password with timing-safe comparison
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        // Rate limiting for login - 5 attempts per minute per email+IP
        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->input(Fortify::username()) ?? ''));
            $ip = $request->ip();
            $key = $email . '|' . $ip;
            
            return Limit::perMinute(5)->by($key);
        });

        // Rate limiting for two-factor
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

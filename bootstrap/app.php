<?php

use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\ValidateJsonRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Disable CSRF for API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Add custom middleware for API routes
        $middleware->api(prepend: [
            ValidateJsonRequest::class,
            SanitizeInput::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for API exceptions
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Custom rate limit response
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many requests. Please slow down.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }
        });
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should never be mass-assigned from request
     */
    protected array $forbiddenFields = [
        'id',
        'created_at',
        'updated_at',
        'email_verified_at',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * Sanitize and filter input data.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Remove forbidden fields from input
        $input = $request->except($this->forbiddenFields);
        
        // Sanitize string inputs
        $sanitized = $this->sanitizeArray($input);
        
        // Replace request input with sanitized data
        $request->replace($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize array values
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                // Strip null bytes
                $data[$key] = str_replace(chr(0), '', $value);
                // Trim whitespace
                $data[$key] = trim($data[$key]);
            }
        }
        return $data;
    }
}

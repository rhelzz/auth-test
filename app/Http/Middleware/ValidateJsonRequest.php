<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonRequest
{
    /**
     * Validate that the request has valid JSON content.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for POST, PUT, PATCH requests with content
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->header('Content-Type');
            
            // Check Content-Type header
            if (!str_contains($contentType ?? '', 'application/json')) {
                return response()->json([
                    'message' => 'Content-Type must be application/json',
                    'errors' => [
                        'content_type' => ['The Content-Type header must be application/json']
                    ]
                ], 415); // Unsupported Media Type
            }

            // Check Accept header
            $accept = $request->header('Accept');
            if (!str_contains($accept ?? '', 'application/json') && $accept !== '*/*') {
                return response()->json([
                    'message' => 'Accept header must be application/json',
                    'errors' => [
                        'accept' => ['The Accept header must be application/json']
                    ]
                ], 406); // Not Acceptable
            }

            // Validate JSON is parseable
            $content = $request->getContent();
            if (!empty($content)) {
                json_decode($content);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'message' => 'Invalid JSON format',
                        'errors' => [
                            'json' => ['The request body must be valid JSON: ' . json_last_error_msg()]
                        ]
                    ], 400); // Bad Request
                }
            }
        }

        return $next($request);
    }
}

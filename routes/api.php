<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Fortify handles authentication routes automatically:
// POST /api/register - Register new user
// POST /api/login - Login user

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Custom logout untuk Sanctum (revoke token)
    Route::post('/logout', function (Request $request) {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logout successful'
        ]);
    });
    
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'created_at'])
        ]);
    });
});

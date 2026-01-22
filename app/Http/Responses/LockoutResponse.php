<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LockoutResponse as LockoutResponseContract;

class LockoutResponse implements LockoutResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'message' => 'Terlalu banyak percobaan login. Akun dikunci sementara.',
            'locked' => true,
        ], 429);
    }
}

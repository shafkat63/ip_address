<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!$request->hasHeader('Authorization')) {
                return response()->json([
                    'errorCode' => 401,
                    'errorMessage' => 'Authorization header not found'
                ], 200);
            }
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'errorCode' => 404,
                    'errorMessage' => 'User not found'
                ], 200);
            }
        } catch (Exception $e) {
            $message = 'Token Invalid';
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                $message = 'Token Expired';
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                $message = 'Token Invalid';
            } else {
                $message = 'Authentication Error: ' . $e->getMessage();
            }

            return response()->json([
                'errorCode' => 401,
                'errorMessage' => $message
            ], 200); // Forced success status
        }

        return $next($request);
    }
}

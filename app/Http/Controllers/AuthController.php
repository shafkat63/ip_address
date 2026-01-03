<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Models\User;
use App\Services\AuditService;
use App\Services\JwtService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        AuditService::log(
            'REGISTER',
            $request,
            $user->id,
            old: null,
            new: [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        );

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'token' => $token,
        ], 201);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = JWTAuth::fromUser($user);

        AuditService::log(
            'LOGIN',
            $request,
            $user->id,
            old: null,
            new: [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        );

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'token' => $token,
        ]);
    }

    public function refresh(Request $request)
    {
        try {
            $token = JWTAuth::parseToken()->refresh();

            $user = JWTAuth::setToken($token)->authenticate();

            return response()->json([
                'message' => 'Token refreshed successfully',
                'token'   => $token,
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token refresh failed'], 401);
        }
    }


    public function logout(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'errorCode' => 401,
                    'errorMessage' => 'User already logged out or session invalid'
                ], 200);
            }

            // Log audit
            AuditService::log('LOGOUT', $request, $user->id, null, [
                'id' => $user->id,
                'email' => $user->email,
            ]);

            JWTAuth::parseToken()->invalidate();

            return response()->json([
                'errorCode' => 0, // 0 usually indicates success in this pattern
                'errorMessage' => 'Successfully logged out'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'errorCode' => 500,
                'errorMessage' => 'Logout error: ' . $e->getMessage()
            ], 200);
        }
    }

    public function userSession(Request $request)
    {
        $sessionId = $request->attributes->get('session_id');

        return AuditLog::where('user_id', Auth::id())
            ->where('session_id', $sessionId)
            ->get();
    }
}

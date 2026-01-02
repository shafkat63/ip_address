<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AuthSession;
use App\Models\User;
use App\Services\AuditService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function userSession(Request $request)
    {
        $tokenId = $request->user()->currentAccessToken()->id;

        return AuditLog::where('user_id', Auth::id())
            ->where('session_id', $tokenId)
            ->get();
    }
    public function refresh(Request $request)
    {
        $request->validate(['refresh_token' => 'required']);

        $payload = JwtService::decode($request->refresh_token);

        $session = AuthSession::where('id', $payload['sid'])
            ->whereNull('revoked_at')
            ->firstOrFail();

        if (!hash_equals(
            $session->refresh_token_hash,
            hash('sha256', $request->refresh_token)
        )) {
            abort(401);
        }

        $accessToken = JwtService::encode([
            'sub' => $payload['sub'],
            'role' => Auth::user()->role,
            'sid' => $payload['sid'],
            'iat' => time(),
            'exp' => time() + 900
        ]);

        AuditService::log('TOKEN_REFRESH', $request);

        return response()->json(['access_token' => $accessToken]);
    }


    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'user',
        ]);

        AuditService::log('REGISTER', $request);

        return response()->json($user, 201);
    }
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //         'password' => 'required|string',
    //     ]);

    //     $credentials = $request->only('email', 'password');

    //     if (!Auth::attempt($credentials)) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     $user = Auth::user();

    //     $previousTokens = $user->tokens()->count();

    //     if ($previousTokens > 0) {
    //         AuditService::log('FORCED_LOGOUT_PREVIOUS', $request);

    //         $user->tokens()->delete();
    //     }

    //     $token = $user->createToken('access')->plainTextToken;

    //     AuditService::log('LOGIN', $request);

    //     return response()->json([
    //         'token' => $token,
    //         'user'  => [
    //             'id'    => $user->id,
    //             'name'  => $user->name,
    //             'email' => $user->email,
    //             'role'  => $user->role,
    //         ],
    //     ]);
    // }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            abort(401);
        }

        $user = Auth::user();

        // Enforce single session
        AuthSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $sid = (string) Str::uuid();

        $accessToken = JwtService::encode([
            'sub' => $user->id,
            'role' => $user->role,
            'sid' => $sid,
            'iat' => time(),
            'exp' => time() + 900
        ]);

        $refreshToken = JwtService::encode([
            'sub' => $user->id,
            'sid' => $sid,
            'iat' => time(),
            'exp' => time() + 604800
        ]);

        AuthSession::create([
            'id' => $sid,
            'user_id' => $user->id,
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'user_agent' => $request->userAgent(),
            'origin_ip' => $request->ip(),
            'expires_at' => now()->addDays(7),
        ]);

        AuditService::log('LOGIN', $request);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ]);
    }

    public function logout(Request $request)
    {
        AuthSession::where('id', $request->attributes->get('session_id'))
            ->update(['revoked_at' => now()]);

        AuditService::log('LOGOUT', $request);

        return response()->noContent();
    }
}

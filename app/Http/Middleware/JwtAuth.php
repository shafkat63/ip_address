<?php

namespace App\Http\Middleware;

use App\Models\AuthSession;
use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            abort(401, 'Missing token');
        }

        try {
            $payload = JwtService::decode($token);
        } catch (\Exception $e) {
            abort(401, $e->getMessage());
        }

        $session = AuthSession::where('id', $payload['sid'])
            ->whereNull('revoked_at')
            ->first();

        if (!$session) {
            abort(401, 'Session revoked');
        }

        $user = User::findOrFail($payload['sub']);
        Auth::setUser($user);

        // make session id available everywhere
        $request->attributes->set('session_id', $payload['sid']);

        return $next($request);
    }
}

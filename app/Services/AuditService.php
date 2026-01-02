<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(
        string $action,
        Request $request,
        ?int $ipId = null,
        $old = null,
        $new = null
    ): void {
        $token = $request->user()?->currentAccessToken();


        AuditLog::create([
            'user_id'       => Auth::id(),
            'ip_address_id' => $ipId,
            'action'        => $action,
            'old_value'     => $old,
            'new_value'     => $new,
            'session_id'    => $token?->id, 
            'origin_ip'     => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ]);
    }
}

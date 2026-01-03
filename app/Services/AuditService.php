<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * Log an action in the audit_logs table
     *
     * @param string $action
     * @param \Illuminate\Http\Request $request
     * @param int|null $userId
     * @param array|null $old
     * @param array|null $new
     * @return void
     */
    public static function log(string $action, Request $request, ?int $userId = null, ?array $old = null, ?array $new = null): void
    {
        // If JWT user exists
        $user = $request->user(); // retrieves user from JWT token

        AuditLog::create([
            'user_id'    => $userId ?? $user?->id,
            'action'     => $action,
            'ip_address' => $request->ip(),
            'old_value'  => $old,
            'new_value'  => $new,
        ]);
    }
}

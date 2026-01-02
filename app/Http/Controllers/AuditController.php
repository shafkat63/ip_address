<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function userSession()
    {
        return AuditLog::where('user_id', Auth::id())
            ->where('session_id', session()->getId())
            ->get();
    }

    public function userLifetime()
    {
        return AuditLog::where('user_id', Auth::id())->get();
    }

    public function ipSession($id)
    {
        return AuditLog::where('ip_address_id', $id)
            ->where('session_id', session()->getId())
            ->get();
    }

    public function ipLifetime($id)
    {
        return AuditLog::where('ip_address_id', $id)->get();
    }
}

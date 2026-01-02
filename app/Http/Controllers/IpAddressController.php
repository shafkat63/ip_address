<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\IpAddress;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpAddressController extends Controller
{
    public function index()
    {
        return IpAddress::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ip_address' => 'required|ip|unique:ip_addresses',
            'label' => 'required|string',
            'comment' => 'nullable|string',
        ]);

        $ip = IpAddress::create($data + ['created_by' => Auth::id()]);
        AuditService::log('CREATE', $request, $ip->id, null, $ip);

        return $ip;
    }

    public function update(Request $request, IpAddress $ip)
    {
        if ($ip->created_by !== Auth::id() && Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        $old = $ip->toArray();
        $ip->update($request->only('label', 'comment'));
        AuditService::log('UPDATE', $request, $ip->id, $old, $ip);

        return $ip;
    }

    public function destroy(Request $request, IpAddress $ip)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);

        $old = $ip->toArray();
        $ip->delete();
        AuditService::log('DELETE', $request, $ip->id, $old, null);
    }
}

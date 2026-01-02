<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = true;
    protected $guarded = ['id'];

    public static function booted()
    {
        static::deleting(fn() => abort(403, 'Audit logs are immutable'));
        static::updating(fn() => abort(403, 'Audit logs are immutable'));
    }
}

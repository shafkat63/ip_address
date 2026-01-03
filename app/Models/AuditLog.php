<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\AuthorizationException;

class AuditLog extends Model
{
    public $timestamps = true;

    protected $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'old_value',
        'new_value',
    ];
    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function ($model) {
            throw new AuthorizationException('Audit logs are immutable.');
        });

        static::deleting(function ($model) {
            throw new AuthorizationException('Audit logs are immutable.');
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthSession extends Model
{
    public $incrementing = false; // UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'refresh_token_hash',
        'user_agent',
        'origin_ip',
        'revoked_at',
        'expires_at',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}

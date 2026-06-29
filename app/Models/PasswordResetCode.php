<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('email', 'code', 'expires_at', 'used_at', 'verified_at')]
class PasswordResetCode extends Model
{
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}

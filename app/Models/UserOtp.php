<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone','code','expires_at','attempts','meta_json',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta_json' => 'array',
    ];
}


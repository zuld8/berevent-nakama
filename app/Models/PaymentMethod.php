<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'channel_id',
        'provider',
        'method_code',
        'config_json',
        'active',
        'created_at',
    ];

    protected $casts = [
        'config_json' => 'array',
        'active' => 'bool',
        'created_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(PaymentChannel::class, 'channel_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'payment_method_id');
    }
}


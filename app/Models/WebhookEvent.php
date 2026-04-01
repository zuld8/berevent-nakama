<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'event_type',
        'raw_body_json',
        'signature',
        'received_at',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'raw_body_json' => 'array',
        'received_at' => 'datetime',
        'processed' => 'bool',
        'processed_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}


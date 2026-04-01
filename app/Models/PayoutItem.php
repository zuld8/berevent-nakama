<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payout_id',
        'source_type',
        'source_id',
        'amount',
        'memo',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}


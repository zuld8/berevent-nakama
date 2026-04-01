<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'payment_method_id',
        'provider_txn_id',
        'provider_status',
        'manual_status',
        'manual_proof_path',
        'manual_note',
        'manual_reviewed_by',
        'manual_reviewed_at',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'payload_req_json',
        'payload_res_json',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'manual_reviewed_at' => 'datetime',
        'payload_req_json' => 'array',
        'payload_res_json' => 'array',
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class, 'transaction_id');
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}

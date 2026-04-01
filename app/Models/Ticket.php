<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id','order_item_id','event_id','code','status','used_at','meta_json',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}


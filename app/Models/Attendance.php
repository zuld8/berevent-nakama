<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id','event_id','material_id','checked_in_at','meta_json',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function material()
    {
        return $this->belongsTo(EventMaterial::class, 'material_id');
    }
}


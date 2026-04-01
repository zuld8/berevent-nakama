<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaServiceSession extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
}


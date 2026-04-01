<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'summary',
        'commitment',
        'address',
        'lat',
        'lng',
        'logo_path',
        'meta_json',
        'social_json',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'bool',
        'meta_json' => 'array',
        'social_json' => 'array',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }
        try {
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($this->logo_path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->logo_path);
        }
    }

    public function wallet()
    {
        return $this->morphOne(\App\Models\Wallet::class, 'owner');
    }
}

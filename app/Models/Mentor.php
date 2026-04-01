<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Mentor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'profession',
        'photo_path',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        $path = (string) ($this->photo_path ?? '');
        if ($path === '') return null;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        } catch (\Throwable) {}

        try {
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            return Storage::disk('s3')->temporaryUrl($path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            try { return Storage::disk('s3')->url($path); } catch (\Throwable) { return null; }
        }
    }

    public function materials()
    {
        return $this->hasMany(\App\Models\EventMaterial::class);
    }
}

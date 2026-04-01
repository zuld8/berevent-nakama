<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Hero extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'campaign_id',
        'image_path',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    // Keep backward compatibility: some parts still reference campaign()
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;
        if (blank($path)) {
            return null;
        }

        // Absolute URL stored
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Try public disk first
        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        } catch (\Throwable) {
            // ignore and try s3 next
        }

        // Try S3 with signed URL similar to CampaignMedia
        try {
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            return Storage::disk('s3')->temporaryUrl($path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            try {
                return Storage::disk('s3')->url($path);
            } catch (\Throwable) {
                // Fallback to default disk URL if available
                try {
                    return Storage::disk(config('filesystems.default', 'public'))->url($path);
                } catch (\Throwable) {
                    return null;
                }
            }
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (Hero $hero) {
            $path = $hero->image_path;
            if (blank($path)) return;

            // Attempt delete on both disks as path may have moved over time
            try {
                \Illuminate\Support\Facades\Storage::disk('s3')->delete($path);
            } catch (\Throwable) {
                // ignore
            }
            try {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            } catch (\Throwable) {
                // ignore
            }
        });
    }
}

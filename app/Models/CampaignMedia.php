<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CampaignMedia extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'type',
        'platform',
        'path',
        'sort_order',
        'meta_json',
        'created_at',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        try {
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            return Storage::disk('s3')->temporaryUrl($this->path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            // Fallback to regular URL if temporaryUrl is not supported
            return Storage::disk('s3')->url($this->path);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (CampaignMedia $media) {
            if (! empty($media->path)) {
                try {
                    Storage::disk('s3')->delete($media->path);
                } catch (\Throwable) {
                    // ignore delete errors
                }
            }
        });
    }
}

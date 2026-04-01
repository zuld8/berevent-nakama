<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'cover_path',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_category');
    }

    public function getCoverUrlAttribute(): ?string
    {
        $path = (string) ($this->cover_path ?? '');
        if ($path === '') return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        } catch (\Throwable) {}
        try {
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            return \Illuminate\Support\Facades\Storage::disk(media_disk())->temporaryUrl($path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            try { return \Illuminate\Support\Facades\Storage::disk(media_disk())->url($path); } catch (\Throwable) { return null; }
        }
    }
}

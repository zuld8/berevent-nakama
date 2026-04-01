<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'category_id',
        'session_count',
        'mode',
        'start_date',
        'end_date',
        'price_type',
        'price',
        'cover_path',
        'type',
        'description',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function materials()
    {
        return $this->hasMany(\App\Models\EventMaterial::class);
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

    // Use slug for route model binding
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->title)) {
                $model->slug = static::uniqueSlug($model->title);
            }
        });
        static::updating(function ($model) {
            if ($model->isDirty('title') && empty($model->slug)) {
                $model->slug = static::uniqueSlug($model->title, $model->id);
            }
        });
    }

    protected static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 1;
        while (static::query()
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }
}

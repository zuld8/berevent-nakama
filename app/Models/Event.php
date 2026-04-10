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
        'meta_json',
        'replay_url',
        'replay_price',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2',
        'meta_json' => 'array',
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

    public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class);
    }

    /** Apakah event ini punya rekaman? */
    public function hasReplay(): bool
    {
        return ! empty($this->replay_url);
    }

    /** Apakah user (berdasarkan user_id) punya tiket untuk event ini? */
    public function userHasTicket(?int $userId): bool
    {
        if (! $userId) return false;
        return \App\Models\Ticket::query()
            ->where('event_id', $this->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('status', 'paid'))
            ->exists();
    }

    /** Apakah user bisa langsung tonton replay (gratis karena punya tiket)? */
    public function userCanWatchFree(?int $userId): bool
    {
        return $this->hasReplay() && $this->userHasTicket($userId);
    }

    /** Harga replay yang harus dibayar (null = tidak dijual) */
    public function replayPriceForSale(): ?int
    {
        if (! $this->hasReplay()) return null;
        if ($this->replay_price === null) return null; // hanya untuk pemilik tiket
        return (int) $this->replay_price;
    }

    /** Apakah user sudah beli replay (via order dengan item replay)? */
    public function userHasBoughtReplay(?int $userId): bool
    {
        if (! $userId) return false;
        return \App\Models\Order::query()
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->whereHas('items', fn ($q) => $q->where('event_id', $this->id)->where('title', 'LIKE', '%[Replay]%'))
            ->exists();
    }
}

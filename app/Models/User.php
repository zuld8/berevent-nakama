<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        $path = (string) ($this->profile_photo_path ?? '');
        if ($path === '') {
            return null;
        }

        // Absolute URL stored
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Try public disk first if exists
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        } catch (\Throwable) {
            // ignore and try s3 next
        }

        // Prefer signed S3 URL similar to Hero/CampaignMedia
        try {
            $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
            return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            try {
                return \Illuminate\Support\Facades\Storage::disk('s3')->url($path);
            } catch (\Throwable) {
                try {
                    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default', 'public'))->url($path);
                } catch (\Throwable) {
                    return null;
                }
            }
        }
    }

    public function contact()
    {
        return $this->hasOne(UserContact::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (string) ($this->type ?? '') === 'admin';
    }
}

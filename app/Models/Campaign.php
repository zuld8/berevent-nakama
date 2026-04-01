<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'summary',
        'meta_title',
        'meta_description',
        'meta_image_url',
        'description_md',
        'target_amount',
        'raised_amount',
        'status',
        'start_date',
        'end_date',
        'settings_json',
        'location_json',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'raised_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'settings_json' => 'array',
        'location_json' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'campaign_category');
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function media()
    {
        return $this->hasMany(CampaignMedia::class);
    }

    protected static function booted(): void
    {
        static::created(function (Campaign $campaign) {
            if (! $campaign->wallet()->exists()) {
                $campaign->wallet()->create(['balance' => 0]);
            }
        });
    }
}

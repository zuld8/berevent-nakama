<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('donations:sync-raised', function () {
    $campaigns = \App\Models\Campaign::all();
    foreach ($campaigns as $campaign) {
        $sum = \App\Models\Donation::where('campaign_id', $campaign->id)
            ->where('status', 'paid')
            ->sum('amount');
        $campaign->raised_amount = $sum;
        $campaign->saveQuietly();
        $this->info("Campaign #{$campaign->id} '{$campaign->title}' => Rp " . number_format($sum, 0, ',', '.'));
    }
    $this->info('Done!');
})->purpose('Sync campaign raised_amount from paid donations');

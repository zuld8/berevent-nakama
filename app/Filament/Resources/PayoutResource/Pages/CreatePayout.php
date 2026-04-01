<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\Wallet;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'pending';
        $data['requested_at'] = now();
        // move optional source campaign info into meta_json
        $meta = $data['meta_json'] ?? [];
        if (!is_array($meta)) { $meta = []; }
        if (!empty($data['source_campaign_id'] ?? null)) {
            $cid = (int) $data['source_campaign_id'];
            $camp = \App\Models\Campaign::find($cid);
            $meta['source_campaign_id'] = $cid;
            if ($camp) { $meta['source_campaign_title'] = $camp->title; }
        }
        $data['meta_json'] = $meta;
        unset($data['source_campaign_id']);
        return $data;
    }
    // Payout debiting moved to approval/completion actions on the list page
}

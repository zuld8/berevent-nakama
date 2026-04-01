<?php

namespace App\Observers;

use App\Models\Donation;
use App\Models\Campaign;

class DonationObserver
{
    /**
     * When a donation's status changes to 'paid', update the campaign raised_amount.
     */
    public function updating(Donation $donation): void
    {
        // Only trigger when status changes TO 'paid'
        if ($donation->isDirty('status') && $donation->status === 'paid') {
            $campaign = Campaign::find($donation->campaign_id);
            if ($campaign) {
                $campaign->raised_amount = (float) $campaign->raised_amount + (float) $donation->amount;
                $campaign->save();
            }
        }
    }
}

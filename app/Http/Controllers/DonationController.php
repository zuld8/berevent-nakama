<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function thanks(Request $request, string $reference)
    {
        $donation = Donation::query()
            ->with(['campaign:id,title,slug,organization_id', 'campaign.organization:id,meta_json'])
            ->where('reference', $reference)
            ->firstOrFail();

        // Determine donor identity preference
        $phone = trim((string) ($donation->donor_phone ?? ''));
        $email = trim((string) ($donation->donor_email ?? ''));
        $userId = $donation->user_id;

        $historyQ = Donation::query()
            ->with(['campaign:id,title,slug'])
            ->where('status', 'paid')
            ->when($phone !== '', fn ($q) => $q->where('donor_phone', $phone))
            ->when($phone === '' && $email !== '', fn ($q) => $q->where('donor_email', $email))
            ->when($phone === '' && $email === '' && $userId, fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('paid_at');

        // Fetch and reduce to unique campaigns (exclude current campaign)
        $historyRows = $historyQ->get(['id','campaign_id','paid_at']);
        $historyCampaigns = [];
        $seen = [];
        foreach ($historyRows as $row) {
            if ($row->campaign_id == $donation->campaign_id) continue;
            $cid = (int) $row->campaign_id;
            if ($cid && !isset($seen[$cid]) && $row->campaign) {
                $historyCampaigns[] = [
                    'title' => (string) $row->campaign->title,
                    'slug' => (string) $row->campaign->slug,
                    'last_paid_at' => optional($row->paid_at)->toDateTimeString(),
                ];
                $seen[$cid] = true;
            }
            if (count($historyCampaigns) >= 5) break; // cap to 5 items
        }

        return view('donation.thanks', [
            'donation' => $donation,
            'historyCampaigns' => $historyCampaigns,
        ]);
    }
}

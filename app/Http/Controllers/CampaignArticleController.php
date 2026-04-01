<?php

namespace App\Http\Controllers;

use App\Models\CampaignArticle;
use App\Models\Organization;
use Illuminate\Http\Request;

class CampaignArticleController extends Controller
{
    public function show(Request $request, int $id, ?string $slug = null)
    {
        $article = CampaignArticle::query()
            ->with(['campaign:id,slug,title,raised_amount,target_amount,description_md', 'payout'])
            ->findOrFail($id);

        $campaign = $article->campaign;

        $org = Organization::query()->first();

        return view('article.show', [
            'a' => $article,
            'c' => $campaign,
            'org' => $org,
        ]);
    }
}

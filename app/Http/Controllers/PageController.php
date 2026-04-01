<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Organization;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $p = Page::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->firstOrFail();

        $org = Organization::query()->first();

        return view('page.show', [
            'p' => $p,
            'org' => $org,
        ]);
    }
}

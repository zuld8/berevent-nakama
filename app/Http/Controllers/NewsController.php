<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Organization;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $perPage = 9;

        $news = News::query()
            ->with(['author:id,name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%$q%")
                        ->orWhere('excerpt', 'like', "%$q%")
                        ->orWhere('body_md', 'like', "%$q%");
                });
            })
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();

        $org = Organization::query()->first();

        return view('news.index', [
            'news' => $news,
            'q' => $q,
            'org' => $org,
        ]);
    }

    public function show(string $slug)
    {
        $n = News::query()
            ->with(['author:id,name'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->firstOrFail();

        // Optional: latest 3 for sidebar/related
        $latest = News::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('id', '!=', $n->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get(['id','title','slug','published_at']);

        $org = Organization::query()->first();

        return view('news.show', [
            'n' => $n,
            'latest' => $latest,
            'org' => $org,
        ]);
    }
}

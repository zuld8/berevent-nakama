<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Category;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\News;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');
        $q = trim((string) $request->query('q'));

        $perPage = 6;

        // Load organization for homepage settings
        $org = Organization::query()->first();

        // Homepage heroes: use Hero records (active) linked to events
        $heroes = \App\Models\Hero::query()
            ->with(['event:id,title'])
            ->where('status', 'active')
            ->whereHas('event')
            ->latest('updated_at')
            ->take(5)
            ->get(['id','event_id','image_path','status','updated_at']);

        $events = Event::query()
            ->with(['category:id,name,slug'])
            ->when($categorySlug, fn ($query) => $query->whereHas('category', fn ($cq) => $cq->where('slug', $categorySlug)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%$q%")
                       ->orWhere('description', 'like', "%$q%");
                });
            })
            ->where('status', 'published')
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END, start_date ASC')
            ->simplePaginate($perPage)
            ->withQueryString();

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'cover_path']);
        $categoryItems = $categories->map(function ($c) {
            $img = method_exists($c, 'getCoverUrlAttribute') ? ($c->cover_url ?? null) : null;
            if (! $img) {
                $img = 'https://ui-avatars.com/api/?name=' . urlencode($c->name) . '&background=E5E7EB&color=111827';
            }
            return ['title' => $c->name, 'img' => $img, 'slug' => $c->slug];
        })->all();

        // Latest published news for homepage (3 items)
        $latestNews = News::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->take(3)
            ->get(['id','title','slug','excerpt','cover_path','published_at','author_id']);

        // Active campaigns for homepage
        $campaigns = Campaign::query()
            ->with('organization')
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->take(6)
            ->get();

        return view('home', [
            'events' => $events,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
            'q' => $q,
            'perPage' => $perPage,
            'org' => $org,
            'heroes' => $heroes,
            'latestNews' => $latestNews,
            'categoryItems' => $categoryItems,
            'campaigns' => $campaigns,
        ]);
    }

    public function chunk(Request $request)
    {
        $categorySlug = $request->query('category');
        $q = trim((string) $request->query('q'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('perPage', 6));

        $events = Event::query()
            ->with(['category:id,name,slug'])
            ->when($categorySlug, fn ($query) => $query->whereHas('category', fn ($cq) => $cq->where('slug', $categorySlug)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%$q%")
                       ->orWhere('description', 'like', "%$q%");
                });
            })
            ->where('status', 'published')
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END, start_date ASC')
            ->simplePaginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        $html = view('partials.event-cards', [
            'events' => $events,
        ])->render();

        return response()->json([
            'html' => $html,
            'hasMore' => $events->hasMorePages(),
            'nextPage' => $events->currentPage() + 1,
        ]);
    }
}

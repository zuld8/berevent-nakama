<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Organization;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');
        $q = trim((string) $request->query('q'));
        $perPage = 9;

        $campaigns = Campaign::query()
            ->with(['categories:id,name,slug', 'media' => function ($q) { $q->orderBy('sort_order'); }])
            ->when($categorySlug, fn ($query) => $query->whereHas('categories', fn ($cq) => $cq->where('slug', $categorySlug)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%$q%")
                       ->orWhere('summary', 'like', "%$q%")
                       ->orWhere('description_md', 'like', "%$q%");
                });
            })
            ->where('status', 'active')
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id','name','slug']);

        $org = Organization::query()->first();

        return view('program.index', [
            'campaigns' => $campaigns,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
            'q' => $q,
            'org' => $org,
        ]);
    }

    public function chunk(Request $request)
    {
        $categorySlug = $request->query('category');
        $q = trim((string) $request->query('q'));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('perPage', 9));

        $campaigns = Campaign::query()
            ->with(['categories:id,name,slug', 'media' => function ($q) { $q->orderBy('sort_order'); }])
            ->when($categorySlug, fn ($query) => $query->whereHas('categories', fn ($cq) => $cq->where('slug', $categorySlug)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%$q%")
                       ->orWhere('summary', 'like', "%$q%")
                       ->orWhere('description_md', 'like', "%$q%");
                });
            })
            ->where('status', 'active')
            ->latest('updated_at')
            ->simplePaginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

        $html = view('partials.campaign-cards', [
            'campaigns' => $campaigns,
        ])->render();

        return response()->json([
            'html' => $html,
            'hasMore' => $campaigns->hasMorePages(),
            'nextPage' => $campaigns->currentPage() + 1,
        ]);
    }
}

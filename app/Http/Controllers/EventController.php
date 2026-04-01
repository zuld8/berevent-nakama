<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');
        $q = trim((string) $request->query('q'));
        $startInput = $request->query('start');
        $endInput = $request->query('end');

        $perPage = (int) ($request->query('perPage', 8));

        $org = Organization::query()->first();

        $startDate = null;
        $endDate = null;
        try { if ($startInput) { $startDate = \Illuminate\Support\Carbon::parse($startInput)->startOfDay(); } } catch (\Throwable) {}
        try { if ($endInput) { $endDate = \Illuminate\Support\Carbon::parse($endInput)->endOfDay(); } } catch (\Throwable) {}

        $events = Event::query()
            ->with(['category:id,name,slug'])
            ->when($categorySlug, fn ($query) => $query->whereHas('category', fn ($cq) => $cq->where('slug', $categorySlug)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%$q%")
                       ->orWhere('description', 'like', "%$q%");
                });
            })
            ->when($startDate || $endDate, function ($query) use ($startDate, $endDate) {
                $query->where(function ($qq) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        // Overlap: (start <= endFilter) AND (end >= startFilter)
                        $qq->where(function ($w) use ($endDate) {
                            $w->whereNull('start_date')->orWhere('start_date', '<=', $endDate);
                        })->where(function ($w) use ($startDate) {
                            $w->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                        });
                    } elseif ($startDate) {
                        $qq->where(function ($w) use ($startDate) {
                            $w->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                        });
                    } elseif ($endDate) {
                        $qq->where(function ($w) use ($endDate) {
                            $w->whereNull('start_date')->orWhere('start_date', '<=', $endDate);
                        });
                    }
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
            if (!$img) {
                $img = 'https://ui-avatars.com/api/?name=' . urlencode($c->name) . '&background=E5E7EB&color=111827';
            }
            return ['title' => $c->name, 'img' => $img, 'slug' => $c->slug];
        })->all();

        return view('event.index', [
            'events' => $events,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
            'q' => $q,
            'start' => $startInput,
            'end' => $endInput,
            'perPage' => $perPage,
            'org' => $org,
            'categoryItems' => $categoryItems,
        ]);
    }

    public function show(Event $event)
    {
        $org = Organization::query()->first();
        $event->load([
            'materials' => function ($q) {
                $q->orderBy('date_at')->orderBy('id');
            },
            'materials.mentor:id,name,profession,photo_path',
        ]);

        return view('event.show', [
            'event' => $event,
            'org' => $org,
        ]);
    }
}

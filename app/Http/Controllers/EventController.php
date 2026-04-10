<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $userId = auth()->id();

        // Replay state
        $canWatchFree    = $event->userCanWatchFree($userId);      // punya tiket → nonton gratis
        $hasBoughtReplay = $event->userHasBoughtReplay($userId);   // udah beli replay
        $canWatch        = $canWatchFree || $hasBoughtReplay;
        $replayPrice     = $event->replayPriceForSale();            // null = ga dijual

        return view('event.show', [
            'event'          => $event,
            'org'            => $org,
            'canWatch'       => $canWatch,
            'replayPrice'    => $replayPrice,
        ]);
    }

    public function buyReplay(Request $request, Event $event)
    {
        $userId = auth()->id();

        // Validasi: event harus punya replay dan dijual
        if (! $event->hasReplay()) {
            return back()->with('error', 'Event ini tidak memiliki rekaman.');
        }

        $price = $event->replayPriceForSale();
        if ($price === null) {
            return back()->with('error', 'Rekaman event ini tidak dijual secara publik.');
        }

        // Kalau sudah punya tiket → gratis, redirect ke event show
        if ($event->userHasTicket($userId) || $event->userHasBoughtReplay($userId)) {
            return redirect()->route('event.show', $event->slug)
                ->with('success', 'Anda sudah memiliki akses ke rekaman ini.');
        }

        // Buat order baru
        $ref = 'RPL-' . strtoupper(Str::random(8)) . '-' . now()->format('ymd');

        $order = Order::create([
            'user_id'      => $userId,
            'reference'    => $ref,
            'total_amount' => $price,
            'status'       => 'pending',
            'meta_json'    => ['type' => 'replay', 'event_id' => $event->id],
        ]);

        OrderItem::create([
            'order_id'   => $order->id,
            'event_id'   => $event->id,
            'title'      => '[Replay] ' . $event->title,
            'unit_price' => $price,
            'qty'        => 1,
        ]);

        // Kalau gratis (price = 0), langsung mark paid
        if ($price === 0) {
            $order->status = 'paid';
            $order->paid_at = now();
            $order->save();
            return redirect()->route('event.show', $event->slug)
                ->with('success', 'Akses rekaman berhasil diaktifkan!');
        }

        return redirect()->route('order.pay', $ref);
    }
}

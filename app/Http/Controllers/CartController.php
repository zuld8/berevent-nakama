<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected function getItems(Request $request): array
    {
        return (array) $request->session()->get('cart.items', []);
    }

    protected function putItems(Request $request, array $items): void
    {
        $request->session()->put('cart.items', $items);
    }

    public function index(Request $request)
    {
        $items = $this->getItems($request);
        $total = 0.0;
        foreach ($items as $it) {
            $total += (float)($it['unit_price'] ?? 0) * (int)($it['qty'] ?? 1);
        }
        return view('cart.index', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function add(Request $request, Event $event)
    {
        // Block if event has expired
        if ($event->end_date && \Illuminate\Support\Carbon::parse($event->end_date)->endOfDay()->isPast()) {
            return redirect()->back()->with('error', 'Event ini sudah berakhir dan tidak bisa dipesan.');
        }

        $items = $this->getItems($request);
        $key = (string) $event->slug;
        $unitPrice = 0.0;

        // Handle fixed price
        if (($event->price_type ?? 'fixed') === 'fixed' && (float)($event->price ?? 0) > 0) {
            $unitPrice = (float) $event->price;
        }
        // Handle dynamic price (donation)
        elseif (($event->price_type ?? 'fixed') !== 'fixed') {
            $minPrice    = (int) ($event->min_price ?? 10000); // pakai min_price event, default 10.000
            $customPrice = $request->input('custom_price');
            if ($customPrice !== null && (float)$customPrice >= $minPrice) {
                $unitPrice = (float) $customPrice;
            } else {
                $formatted = 'Rp ' . number_format($minPrice, 0, ',', '.');
                return redirect()->back()->with('error', "Silakan pilih nominal donasi minimal {$formatted}");
            }
        }

        if (!isset($items[$key])) {
            $items[$key] = [
                'id'         => $event->id,
                'slug'       => $event->slug,
                'title'      => $event->title,
                'cover_url'  => $event->cover_url,
                'unit_price' => $unitPrice,
                'price_type' => $event->price_type,
                'qty'        => 1,
                'item_type'  => 'ticket',
            ];
        } else {
            $items[$key]['qty'] = (int)($items[$key]['qty'] ?? 1) + 1;
            // Update price for dynamic pricing if provided
            if (($event->price_type ?? 'fixed') !== 'fixed' && $request->input('custom_price')) {
                $items[$key]['unit_price'] = $unitPrice;
            }
        }
        $this->putItems($request, $items);
        return redirect()->route('cart.index')->with('status', 'Ditambahkan ke keranjang');
    }

    /**
     * Tambahkan rekaman (replay) event ke keranjang.
     * Key: 'replay:{slug}' — selalu qty 1, tidak bisa multiple.
     */
    public function addReplay(Request $request, Event $event)
    {
        $userId = auth()->id();

        if (! $event->hasReplay()) {
            return redirect()->back()->with('error', 'Event ini tidak memiliki rekaman.');
        }

        $price = $event->replayPriceForSale();
        if ($price === null) {
            return redirect()->back()->with('error', 'Rekaman event ini tidak dijual secara publik.');
        }

        if ($event->userHasTicket($userId) || $event->userHasBoughtReplay($userId)) {
            return redirect()->route('event.show', $event->slug)
                ->with('success', 'Anda sudah memiliki akses ke rekaman ini.');
        }

        $items = $this->getItems($request);
        $key = 'replay:' . $event->slug;

        if (!isset($items[$key])) {
            $items[$key] = [
                'id'         => $event->id,
                'slug'       => $event->slug,
                'title'      => '[Rekaman] ' . $event->title,
                'cover_url'  => $event->cover_url,
                'unit_price' => (float) $price,
                'price_type' => 'fixed',
                'qty'        => 1,
                'item_type'  => 'replay',
            ];
            $this->putItems($request, $items);
            return redirect()->route('cart.index')->with('status', 'Rekaman ditambahkan ke keranjang');
        }

        return redirect()->route('cart.index')->with('status', 'Rekaman sudah ada di keranjang');
    }

    /**
     * Beli Sekarang — add ticket to cart then redirect straight to checkout.
     */
    public function buyNow(Request $request, Event $event)
    {
        // Reuse add() logic then redirect to checkout
        $response = $this->add($request, $event);

        // If add() returned a redirect back with error, pass it through
        if ($response->isRedirect(route('cart.index'))) {
            return redirect()->route('order.checkout');
        }
        return $response; // redirect back with error
    }

    /**
     * Beli Rekaman Sekarang — add replay to cart then redirect straight to checkout.
     */
    public function buyNowReplay(Request $request, Event $event)
    {
        $response = $this->addReplay($request, $event);

        if ($response->isRedirect(route('cart.index'))) {
            return redirect()->route('order.checkout');
        }
        return $response;
    }

    public function update(Request $request, Event $event)
    {
        $qty = max(0, (int) $request->input('qty', 1));
        $items = $this->getItems($request);
        $key = (string) $event->slug;
        if (isset($items[$key])) {
            if ($qty <= 0) {
                unset($items[$key]);
            } else {
                $items[$key]['qty'] = $qty;
            }
            $this->putItems($request, $items);
        }
        return redirect()->route('cart.index');
    }

    public function remove(Request $request, Event $event)
    {
        $items = $this->getItems($request);
        $key = (string) $event->slug;
        if (isset($items[$key])) {
            unset($items[$key]);
            $this->putItems($request, $items);
        }
        return redirect()->route('cart.index');
    }

    public function removeReplay(Request $request, Event $event)
    {
        $items = $this->getItems($request);
        $key = 'replay:' . $event->slug;
        if (isset($items[$key])) {
            unset($items[$key]);
            $this->putItems($request, $items);
        }
        return redirect()->route('cart.index');
    }

    public function clear(Request $request)
    {
        $request->session()->forget('cart.items');
        return redirect()->route('cart.index');
    }
}

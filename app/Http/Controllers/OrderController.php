<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\Payments\DuitkuService;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $items = (array) $request->session()->get('cart.items', []);
        if (empty($items)) {
            return redirect()->route('cart.index');
        }
        $total = 0.0;
        foreach ($items as $it) {
            $total += (float)($it['unit_price'] ?? 0) * (int)($it['qty'] ?? 1);
        }

        return view('order.checkout', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'prices'     => ['array'],
            'prices.*'   => ['nullable', 'numeric', 'min:0'],
            'pay_method' => ['nullable', 'in:manual,automatic'],
        ]);

        $postedPrices = (array) ($data['prices'] ?? []);
        $payMethod    = (string) ($data['pay_method'] ?? 'manual');

        $items = (array) $request->session()->get('cart.items', []);
        if (empty($items)) {
            return redirect()->route('cart.index');
        }

        $total = 0.0;
        foreach ($items as $slug => $it) {
            $event = \App\Models\Event::query()->where('slug', $it['slug'])->first();
            if (! $event) { continue; }
            $qty = (int) ($it['qty'] ?? 1);
            if (($event->price_type ?? 'fixed') === 'fixed') {
                $unit = (float) ($event->price ?? 0);
            } else {
                $unit = (float) ($postedPrices[$slug] ?? ($it['unit_price'] ?? 0));
                if ($unit < 0) { $unit = 0; }
            }
            $items[$slug]['unit_price'] = $unit;
            $items[$slug]['price_type'] = $event->price_type;
            $total += $unit * $qty;
        }

        $ref   = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $order = Order::create([
            'user_id'      => auth()->id(),
            'reference'    => $ref,
            'total_amount' => $total,
            'status'       => 'pending',
            'meta_json'    => ['payment_type' => $payMethod],
        ]);

        foreach ($items as $it) {
            $event = Event::query()->where('slug', $it['slug'])->first();
            if (! $event) { continue; }
            OrderItem::create([
                'order_id'   => $order->id,
                'event_id'   => $event->id,
                'title'      => $it['title'],
                'unit_price' => (float) (($event->price_type === 'fixed')
                                    ? ($event->price ?? 0)
                                    : ($it['unit_price'] ?? 0)),
                'qty'        => (int) ($it['qty'] ?? 1),
            ]);
        }

        // Clear cart
        $request->session()->forget('cart.items');

        // Auto-paid when total is 0 (free items)
        if ($total <= 0) {
            $order->status  = 'paid';
            $order->paid_at = now();
            $order->save();
            try { \App\Services\TicketIssuer::issueForOrder($order); } catch (\Throwable) {}
            return redirect()->route('order.thanks', $order->reference);
        }

        // Route to the correct payment flow
        if ($payMethod === 'automatic') {
            // Duitku: create transaction and redirect to paymentUrl
            $order->loadMissing('items');
            $duitku = new DuitkuService();
            $baseAmount = max(1, (int) round((float) $order->total_amount));

            $itemDetails = $order->items->map(fn ($it) => [
                'name'     => Str::limit($it->title, 255, ''),
                'price'    => max(0, (int) round((float) $it->unit_price)),
                'quantity' => (int) $it->qty,
            ])->values()->all();

            $uniqueOrderId = $order->reference . '-R' . now()->format('His') . '-' . Str::upper(Str::random(3));

            $res = $duitku->createTransactionForOrder($order, [
                'override_gross'    => $baseAmount,
                'item_details'      => $itemDetails,
                'override_order_id' => $uniqueOrderId,
            ]);

            // Persist to order meta
            $meta = $order->meta_json ?? [];
            $meta['duitku'] = [
                'payment_url'  => $res['paymentUrl'],
                'reference'    => $res['reference'] ?? null,
                'order_id'     => $uniqueOrderId,
                'gross_amount' => $res['grossAmount'],
            ];
            $order->meta_json = $meta;
            $order->save();

            return redirect()->away($res['paymentUrl']);
        }

        // Manual transfer
        return redirect()->route('order.manual', $order->reference);
    }
}

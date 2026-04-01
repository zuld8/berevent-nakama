<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\Payments\PaymentMethodCatalog;
use App\Services\Payments\MidtransService;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $items = (array) $request->session()->get('cart.items', []);
        if (empty($items)) {
            return redirect()->route('cart.index');
        }
        $total = 0.0;
        foreach ($items as $it) { $total += (float)($it['unit_price'] ?? 0) * (int)($it['qty'] ?? 1); }
        $catalog = new \App\Services\Payments\PaymentMethodCatalog();
        $methods = $catalog->activeMidtrans();
        return view('order.checkout', [
            'items' => $items,
            'total' => $total,
            'methods' => $methods,
            'catalog' => $catalog,
        ]);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'prices' => ['array'],
            'prices.*' => ['nullable','numeric','min:0'],
            'pay_method' => ['nullable','in:manual,automatic'],
            'payment_method' => ['nullable','string','max:50'],
        ]);
        $postedPrices = (array) ($data['prices'] ?? []);
        $payMethod = (string) ($data['pay_method'] ?? 'manual');
        $chosenMethod = (string) ($data['payment_method'] ?? '');
        $items = (array) $request->session()->get('cart.items', []);
        if (empty($items)) {
            return redirect()->route('cart.index');
        }
        $total = 0.0;
        // Recompute total with dynamic prices for donation-type items
        foreach ($items as $slug => $it) {
            $event = \App\Models\Event::query()->where('slug', $it['slug'])->first();
            if (! $event) { continue; }
            $qty = (int) ($it['qty'] ?? 1);
            if (($event->price_type ?? 'fixed') === 'fixed') {
                $unit = (float) ($event->price ?? 0);
            } else {
                $unit = (float) ($postedPrices[$slug] ?? ($it['unit_price'] ?? 0));
                if ($unit < 0) $unit = 0;
            }
            // Update cart item reference for order creation consistency
            $items[$slug]['unit_price'] = $unit;
            $items[$slug]['price_type'] = $event->price_type;
            $total += $unit * $qty;
        }
        $ref = 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $order = Order::create([
            'user_id' => auth()->id(),
            'reference' => $ref,
            'total_amount' => $total,
            'status' => 'pending',
            'meta_json' => [
                'payment_type' => $payMethod,
                'midtrans' => $payMethod === 'automatic' && $chosenMethod !== '' ? ['chosen_method' => $chosenMethod] : null,
            ],
        ]);
        foreach ($items as $it) {
            $event = Event::query()->where('slug', $it['slug'])->first();
            if (!$event) continue;
            OrderItem::create([
                'order_id' => $order->id,
                'event_id' => $event->id,
                'title' => $it['title'],
                // Keep fixed price, otherwise use dynamic value
                'unit_price' => (float) (($event->price_type === 'fixed') ? ($event->price ?? 0) : ($it['unit_price'] ?? 0)),
                'qty' => (int) ($it['qty'] ?? 1),
            ]);
        }
        // Clear cart
        $request->session()->forget('cart.items');

        // Auto-paid when total is 0 (free items)
        if ($total <= 0) {
            $order->status = 'paid';
            $order->paid_at = now();
            $order->save();
            try { \App\Services\TicketIssuer::issueForOrder($order); } catch (\Throwable $e) {}
            return redirect()->route('order.thanks', $order->reference);
        }

        // If automatic payment, ensure valid method
        if ($payMethod === 'automatic') {
            $catalog = new \App\Services\Payments\PaymentMethodCatalog();
            $active = collect($catalog->activeMidtrans())->pluck('id')->all();
            if (! in_array($chosenMethod, $active, true)) {
                return back()->withErrors(['payment_method' => 'Silakan pilih metode pembayaran otomatis']).withInput();
            }
        }

        if ($payMethod === 'automatic') {
            // Immediately create Snap session and redirect user to gateway
            $catalog = new PaymentMethodCatalog();
            $sel = $chosenMethod; // validated above
            $enabledPayments = match ($sel) {
                'qris' => ['gopay'],
                default => [$sel],
            };
            $feeInfo = $catalog->computeFee((float) $order->total_amount, $sel);
            $baseAmount = max(1, (int) round((float) $order->total_amount));
            $feeAmount = max(0, (int) round((float) ($feeInfo['amount'] ?? 0)));
            $grossWithFee = $baseAmount + $feeAmount;

            // Build item details
            $order->loadMissing('items');
            $items = $order->items->map(function ($it) {
                return [
                    'id' => 'event_' . $it->event_id,
                    'price' => max(0, (int) round((float) $it->unit_price)),
                    'quantity' => (int) $it->qty,
                    'name' => \Illuminate\Support\Str::limit($it->title, 50, ''),
                ];
            })->values()->all();
            if ($feeAmount > 0) {
                $items[] = [ 'id' => 'admin_fee', 'price' => $feeAmount, 'quantity' => 1, 'name' => 'Biaya Admin' ];
            }

            $mid = new MidtransService();
            $res = $mid->createSnapTransactionForOrder($order, [
                'enabled_payments' => $enabledPayments,
                'override_gross' => $grossWithFee,
                'item_details' => $items,
            ]);

            // Persist meta for reference
            $meta = $order->meta_json ?? [];
            $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
                'chosen_method' => $sel,
                'snap_token' => $res['token'] ?? null,
                'redirect_url' => $res['redirect_url'] ?? null,
                'computed_fee' => $feeAmount,
            ];
            $order->meta_json = $meta;
            $order->save();

            if (!empty($res['redirect_url'])) {
                return redirect()->away($res['redirect_url']);
            }
            return redirect()->route('order.pay', $order->reference);
        }

        return redirect()->route('order.manual', $order->reference);
    }
}

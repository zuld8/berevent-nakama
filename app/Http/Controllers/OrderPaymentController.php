<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use App\Services\Payments\DuitkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderPaymentController extends Controller
{
    public function manual(Request $request, string $reference)
    {
        $order = \App\Models\Order::query()->where('reference', $reference)->firstOrFail();
        $org   = \App\Models\Organization::query()->first();
        $meta  = $org?->meta_json ?? [];
        $bank  = [
            'name'           => data_get($meta, 'payments.manual.bank_name'),
            'account_name'   => data_get($meta, 'payments.manual.bank_account_name'),
            'account_number' => data_get($meta, 'payments.manual.bank_account_number'),
            'instructions'   => data_get($meta, 'payments.manual.instructions'),
            'qr_path'        => data_get($meta, 'payments.manual.qr_path'),
        ];
        $qrUrl = null;
        if (! empty($bank['qr_path'])) {
            try { $qrUrl = \Illuminate\Support\Facades\Storage::disk(media_disk())->temporaryUrl($bank['qr_path'], now()->addMinutes(10)); }
            catch (\Throwable) { $qrUrl = \Illuminate\Support\Facades\Storage::disk(media_disk())->url($bank['qr_path']); }
        }
        return view('order.manual', compact('order', 'bank', 'qrUrl'));
    }

    public function submitManual(Request $request, string $reference)
    {
        $order = \App\Models\Order::query()->where('reference', $reference)->firstOrFail();
        $data  = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'note'  => ['nullable', 'string', 'max:1000'],
        ]);
        $path  = $request->file('proof')->store('orders/manual/proofs', ['disk' => media_disk(), 'visibility' => 'private']);
        $meta  = $order->meta_json ?? [];
        $meta['manual'] = [
            'status'       => 'pending',
            'proof_path'   => $path,
            'note'         => $data['note'] ?? null,
            'submitted_at' => now()->toISOString(),
        ];
        $order->meta_json = $meta;
        $order->save();
        return redirect()->route('order.thanks', $order->reference);
    }

    public function pay(Request $request, string $reference)
    {
        $order = Order::query()->with('items')->where('reference', $reference)->firstOrFail();

        // If paymentUrl already stored, redirect again
        $meta       = $order->meta_json ?? [];
        $paymentUrl = $meta['duitku']['payment_url'] ?? null;

        if (! $paymentUrl) {
            $duitku = new DuitkuService();

            // Ensure channel & method
            $channel = PaymentChannel::firstOrCreate(
                ['code' => 'DUITKU'],
                ['name' => 'Duitku', 'active' => true]
            );
            $method = PaymentMethod::firstOrCreate(
                ['provider' => 'duitku', 'method_code' => 'auto'],
                ['channel_id' => $channel->id, 'config_json' => null, 'active' => true, 'created_at' => now()]
            );

            $baseAmount  = max(1, (int) round((float) $order->total_amount));

            // Build item details
            $items = $order->items->map(function ($it) {
                return [
                    'name'     => Str::limit($it->title, 255, ''),
                    'price'    => max(0, (int) round((float) $it->unit_price)),
                    'quantity' => (int) $it->qty,
                ];
            })->values()->all();

            // Unique order id to avoid duplicate rejection on retry
            $uniqueOrderId = $order->reference . '-R' . now()->format('His') . '-' . Str::upper(Str::random(3));

            $res = $duitku->createTransactionForOrder($order, [
                'override_gross'    => $baseAmount,
                'item_details'      => $items,
                'override_order_id' => $uniqueOrderId,
            ]);

            $paymentUrl = $res['paymentUrl'];

            // Persist to order meta
            $meta['duitku'] = ($meta['duitku'] ?? []) + [
                'payment_url'  => $paymentUrl,
                'reference'    => $res['reference'] ?? null,
                'order_id'     => $uniqueOrderId,
                'gross_amount' => $res['grossAmount'],
            ];
            $order->meta_json = $meta;
            $order->save();
        }

        return redirect()->away($paymentUrl);
    }

    public function thanks(Request $request, string $reference)
    {
        $order = Order::query()->where('reference', $reference)->firstOrFail();
        return view('order.thanks', ['order' => $order]);
    }

    /**
     * Duitku callback is handled centrally in PaymentController::notify()
     * This stub exists for backward compat if old route is still called.
     */
    public function notify(Request $request)
    {
        return app(PaymentController::class)->notify($request);
    }
}

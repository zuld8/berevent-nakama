<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use App\Services\Payments\MidtransService;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function manual(Request $request, string $reference)
    {
        $order = \App\Models\Order::query()->where('reference', $reference)->firstOrFail();
        $org = \App\Models\Organization::query()->first();
        $meta = $org?->meta_json ?? [];
        $bank = [
            'name' => data_get($meta, 'payments.manual.bank_name'),
            'account_name' => data_get($meta, 'payments.manual.bank_account_name'),
            'account_number' => data_get($meta, 'payments.manual.bank_account_number'),
            'instructions' => data_get($meta, 'payments.manual.instructions'),
            'qr_path' => data_get($meta, 'payments.manual.qr_path'),
        ];
        $qrUrl = null;
        if (! empty($bank['qr_path'])) {
            try { $qrUrl = \Illuminate\Support\Facades\Storage::disk(media_disk())->temporaryUrl($bank['qr_path'], now()->addMinutes(10)); }
            catch (\Throwable) { $qrUrl = \Illuminate\Support\Facades\Storage::disk(media_disk())->url($bank['qr_path']); }
        }
        return view('order.manual', compact('order','bank','qrUrl'));
    }

    public function submitManual(Request $request, string $reference)
    {
        $order = \App\Models\Order::query()->where('reference', $reference)->firstOrFail();
        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $path = $request->file('proof')->store('orders/manual/proofs', ['disk' => media_disk(), 'visibility' => 'private']);
        $meta = $order->meta_json ?? [];
        $meta['manual'] = [
            'status' => 'pending',
            'proof_path' => $path,
            'note' => $data['note'] ?? null,
            'submitted_at' => now()->toISOString(),
        ];
        $order->meta_json = $meta;
        $order->save();
        return redirect()->route('order.thanks', $order->reference);
    }
    public function pay(Request $request, string $reference)
    {
        $order = Order::query()->with('items')->where('reference', $reference)->firstOrFail();
        $midtrans = new MidtransService();
        $catalog = new \App\Services\Payments\PaymentMethodCatalog();

        $sel = (string) data_get($order->meta_json, 'midtrans.chosen_method', '');
        $enabledPayments = null;
        if ($sel !== '') {
            $enabledPayments = match ($sel) {
                'qris' => ['gopay'],
                default => [$sel],
            };
        }

        // Compute admin fee like campaign
        $feeInfo = ['amount' => 0];
        if ($sel !== '') {
            $feeInfo = $catalog->computeFee((float) $order->total_amount, $sel);
        }
        $baseAmount = max(1, (int) round((float) $order->total_amount));
        $feeAmount = max(0, (int) round((float) ($feeInfo['amount'] ?? 0)));
        $grossWithFee = $baseAmount + $feeAmount;

        // Build item details
        $items = $order->items->map(function ($it) {
            return [
                'id' => 'event_' . $it->event_id,
                'price' => max(0, (int) round((float) $it->unit_price)),
                'quantity' => (int) $it->qty,
                'name' => \Illuminate\Support\Str::limit($it->title, 50, ''),
            ];
        })->values()->all();
        if ($feeAmount > 0) {
            $items[] = [
                'id' => 'admin_fee',
                'price' => $feeAmount,
                'quantity' => 1,
                'name' => 'Biaya Admin',
            ];
        }

        // Ensure channel & method exist (Midtrans Snap)
        $channel = PaymentChannel::firstOrCreate(
            ['code' => 'MIDTRANS'],
            ['name' => 'Midtrans', 'active' => true]
        );
        $method = PaymentMethod::firstOrCreate(
            ['provider' => 'midtrans', 'method_code' => 'snap'],
            ['channel_id' => $channel->id, 'config_json' => null, 'active' => true, 'created_at' => now()]
        );

        // Always use a fresh order_id to avoid Midtrans duplicate order_id error on retries/expired
        $uniqueOrderId = $order->reference . '-R' . now()->format('His') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(3));
        $res = $midtrans->createSnapTransactionForOrder($order, [
            'enabled_payments' => $enabledPayments,
            'override_gross' => $grossWithFee,
            'item_details' => $items,
            'override_order_id' => $uniqueOrderId,
        ]);

        // For orders, we currently do not persist into 'payments' table
        // to avoid FK constraint to donations. Status will be tracked on Order.

        // Persist reference details to order meta for traceability
        $meta = $order->meta_json ?? [];
        $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
            'order_id' => $uniqueOrderId,
            'snap_token' => $res['token'] ?? null,
            'redirect_url' => $res['redirect_url'] ?? null,
        ];
        $order->meta_json = $meta;
        $order->save();

        return view('order.pay', [
            'order' => $order,
            'snapToken' => $res['token'],
            'clientKey' => $midtrans->clientKey(),
            'snapJsUrl' => $midtrans->snapJsUrl(),
        ]);
    }

    public function thanks(Request $request, string $reference)
    {
        $order = Order::query()->where('reference', $reference)->firstOrFail();
        return view('order.thanks', ['order' => $order]);
    }

    public function notify(Request $request)
    {
        $payload = $request->all();
        $midtrans = new MidtransService();
        if (! $midtrans->validateNotificationSignature($payload)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }
        $orderId = $payload['order_id'] ?? null;
        $order = $orderId ? Order::query()->where('reference', $orderId)->first() : null;
        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $status = $payload['transaction_status'] ?? 'pending';
        if (in_array($status, ['capture','settlement'])) {
            $order->status = 'paid';
            $order->paid_at = now();
            // Issue tickets when paid
            try { \App\Services\TicketIssuer::issueForOrder($order); } catch (\Throwable $e) {}
        } elseif (in_array($status, ['deny','cancel','expire'])) {
            $order->status = 'failed';
        } else {
            $order->status = 'pending';
        }
        $meta = $order->meta_json ?? [];
        $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
            'notification' => $payload,
            'last_updated_at' => now()->toISOString(),
        ];
        $order->meta_json = $meta;
        $order->save();

        // Skipping Payment model persistence for orders

        return response()->json(['message' => 'ok']);
    }
}

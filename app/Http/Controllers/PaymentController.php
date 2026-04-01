<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use App\Models\WebhookEvent;
use App\Services\Payments\MidtransService;
use App\Services\Payments\PaymentMethodCatalog;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Services\WaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function manual(Request $request, string $reference)
    {
        $donation = Donation::query()->with('campaign.organization')->where('reference', $reference)->firstOrFail();

        // Ensure channel & method exist (Manual Transfer)
        $channel = PaymentChannel::firstOrCreate(
            ['code' => 'MANUAL'],
            ['name' => 'Manual', 'active' => true]
        );
        $method = PaymentMethod::firstOrCreate(
            ['provider' => 'manual', 'method_code' => 'transfer'],
            ['channel_id' => $channel->id, 'config_json' => null, 'active' => true, 'created_at' => now()]
        );

        // Create or reuse payment record for manual
        $payment = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();
        if (! $payment || $payment->payment_method_id !== $method->id) {
            $payment = Payment::create([
                'transaction_id' => $donation->id,
                'payment_method_id' => $method->id,
                'provider_txn_id' => $donation->reference,
                'provider_status' => 'pending',
                'manual_status' => 'pending',
                'gross_amount' => $donation->amount,
                'fee_amount' => 0,
                'net_amount' => $donation->amount,
            ]);
        }

        $org = $donation->campaign?->organization;
        $meta = $org?->meta_json ?? [];
        $bank = [
            'name' => data_get($meta, 'payments.manual.bank_name'),
            'account_name' => data_get($meta, 'payments.manual.bank_account_name'),
            'account_number' => data_get($meta, 'payments.manual.bank_account_number'),
            'instructions' => data_get($meta, 'payments.manual.instructions'),
            'qr_path' => data_get($meta, 'payments.manual.qr_path'),
        ];

        // Resolve QR temporary URL if exists
        $qrUrl = null;
        if (! empty($bank['qr_path'])) {
            try {
                $qrUrl = Storage::disk(media_disk())->temporaryUrl($bank['qr_path'], now()->addMinutes(10));
            } catch (\Throwable) {
                $qrUrl = Storage::disk(media_disk())->url($bank['qr_path']);
            }
        }

        return view('donation.manual', [
            'donation' => $donation,
            'payment' => $payment,
            'bank' => $bank,
            'qrUrl' => $qrUrl,
        ]);
    }

    public function submitManual(Request $request, string $reference)
    {
        $donation = Donation::query()->with('campaign')->where('reference', $reference)->firstOrFail();

        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();
        if (! $payment) {
            return back()->withErrors(['proof' => 'Transaksi manual tidak ditemukan.'])->withInput();
        }

        $path = $request->file('proof')->store('manual-payments/proofs', ['disk' => media_disk(), 'visibility' => 'private']);
        $payment->manual_proof_path = $path;
        $payment->manual_note = $data['note'] ?? null;
        $payment->manual_status = 'pending';
        $payment->save();

        return redirect()->route('donation.thanks', ['reference' => $donation->reference]);
    }

    public function pay(Request $request, string $reference)
    {
        $donation = Donation::query()
            ->with(['campaign:id,title,slug,organization_id', 'campaign.organization:id,meta_json'])
            ->where('reference', $reference)
            ->firstOrFail();

        $midtrans = new MidtransService();
        $enabled = null;
        // Optional: if user pre-selected a method, restrict Snap to that
        $sel = (string) data_get($donation->meta_json, 'midtrans.chosen_method', '');
        if ($sel !== '') { $enabled = $sel; }

        // Reuse token if already exists in meta
        $meta = $donation->meta_json ?? [];
        $token = $meta['midtrans']['snap_token'] ?? null;
        if (! $token) {
            // Ensure channel & method exist (Midtrans Snap)
            $channel = PaymentChannel::firstOrCreate(
                ['code' => 'MIDTRANS'],
                ['name' => 'Midtrans', 'active' => true]
            );
            $method = PaymentMethod::firstOrCreate(
                ['provider' => 'midtrans', 'method_code' => 'snap'],
                ['channel_id' => $channel->id, 'config_json' => null, 'active' => true, 'created_at' => now()]
            );

            // If a specific method chosen, compute admin fee and set gross amount
            $catalog = new PaymentMethodCatalog();
            $feeInfo = ['amount' => 0];
            if ($enabled) {
                $feeInfo = $catalog->computeFee($donation->amount, $enabled);
            }
            $baseAmount = max(1, (int) round((float) $donation->amount));
            $feeAmount = max(0, (int) round((float) ($feeInfo['amount'] ?? 0)));
            $grossWithFee = $baseAmount + $feeAmount;

            $itemName = Str::limit('Donasi: ' . ($donation->campaign?->title ?? 'Campaign'), 50, '');
            $items = [[
                'id' => 'donation',
                'price' => $baseAmount,
                'quantity' => 1,
                'name' => $itemName,
            ]];
            if ($feeAmount > 0) {
                $items[] = [
                    'id' => 'admin_fee',
                    'price' => $feeAmount,
                    'quantity' => 1,
                    'name' => Str::limit('Biaya Admin', 50, ''),
                ];
            }

            // Map our method code to Snap `enabled_payments`
            $enabledPayments = null;
            if ($enabled) {
                $enabledPayments = match ($enabled) {
                    // Midtrans Snap exposes QRIS under GoPay channel
                    'qris' => ['gopay'],
                    default => [$enabled],
                };
            }

            $res = $midtrans->createSnapTransaction($donation, [
                'enabled_payments' => $enabledPayments,
                'override_gross' => $grossWithFee,
                'item_details' => $items,
            ]);
            $token = $res['token'];
            $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
                'snap_token' => $token,
                'order_id' => $res['order_id'] ?? $donation->reference,
                'redirect_url' => $res['redirect_url'] ?? null,
                'computed_fee' => $feeAmount,
            ];
            $donation->meta_json = $meta;
            $donation->save();

            // Create payment record if not exists for this donation
            $existing = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();
            if (! $existing) {
                $reqPayload = $res['request'] ?? [];
                $reqPayload['computed'] = ($reqPayload['computed'] ?? []) + [
                    'admin_fee' => $feeAmount,
                    'admin_fee_currency' => 'IDR',
                    'admin_fee_method' => $enabled ?: null,
                ];
                Payment::create([
                    'transaction_id' => $donation->id,
                    'payment_method_id' => $method->id,
                    'provider_txn_id' => $res['order_id'] ?? $donation->reference,
                    'provider_status' => 'initiated',
                    'gross_amount' => $grossWithFee,
                    'fee_amount' => $feeAmount,
                    'net_amount' => $baseAmount,
                    'payload_req_json' => $reqPayload,
                    'payload_res_json' => $res['response'] ?? null,
                ]);
            } else {
                // Update amounts if already exists
                $existing->gross_amount = $grossWithFee;
                $existing->fee_amount = $feeAmount;
                $existing->net_amount = $baseAmount;
                $reqPayload = $existing->payload_req_json ?? [];
                $reqPayload['computed'] = ($reqPayload['computed'] ?? []) + [
                    'admin_fee' => $feeAmount,
                    'admin_fee_currency' => 'IDR',
                    'admin_fee_method' => $enabled ?: null,
                ];
                $existing->payload_req_json = $reqPayload;
                $existing->save();
            }
        }

        return view('donation.pay', [
            'donation' => $donation,
            'snapToken' => $token,
            'clientKey' => $midtrans->clientKey(),
            'snapJsUrl' => $midtrans->snapJsUrl(),
        ]);
    }

    /** Show method list (Midtrans) allowing on/off + fee display. */
    public function methods(Request $request, string $reference)
    {
        $donation = Donation::query()
            ->with(['campaign:id,title,slug,organization_id', 'campaign.organization:id,meta_json'])
            ->where('reference', $reference)
            ->firstOrFail();
        $catalog = new PaymentMethodCatalog();
        $methods = $catalog->activeMidtrans();
        return view('donation.methods', [
            'donation' => $donation,
            'methods' => $methods,
            'catalog' => $catalog,
        ]);
    }

    /** Accept chosen method and proceed to Snap flow. */
    public function choose(Request $request, string $reference)
    {
        $donation = Donation::query()->where('reference', $reference)->firstOrFail();
        $catalog = new PaymentMethodCatalog();
        $methods = collect($catalog->activeMidtrans());

        $data = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);
        $code = $data['payment_method'];
        if (! $methods->pluck('id')->contains($code)) {
            return back()->withErrors(['payment_method' => 'Metode tidak tersedia'])->withInput();
        }

        $meta = $donation->meta_json ?? [];
        $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
            'chosen_method' => $code,
        ];
        $donation->meta_json = $meta;
        $donation->save();

        return redirect()->route('donation.pay', ['reference' => $donation->reference]);
    }

    public function notify(Request $request)
    {
        $payload = $request->all();

        $midtrans = new MidtransService();
        if (! $midtrans->validateNotificationSignature($payload)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $orderId = $payload['order_id'] ?? null;
        if (! $orderId) {
            return response()->json(['message' => 'Missing order_id'], 422);
        }

        $donation = Donation::query()->where('reference', $orderId)->first();
        if (! $donation) {
            return response()->json(['message' => 'Donation not found'], 404);
        }
        // Find latest payment for this donation
        $payment = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();

        // Record webhook first
        $webhook = WebhookEvent::create([
            'payment_id' => $payment?->id,
            'event_type' => $payload['transaction_status'] ?? null,
            'raw_body_json' => $payload,
            'signature' => $payload['signature_key'] ?? null,
            'received_at' => now(),
            'processed' => false,
        ]);

        $map = $midtrans->mapTransactionStatusToDonation($payload);

        // Update payment info
        if ($payment) {
            $payment->provider_txn_id = $payload['transaction_id'] ?? ($payload['order_id'] ?? $payment->provider_txn_id);
            $payment->provider_status = $payload['transaction_status'] ?? $payment->provider_status;
            $gross = (float) ($payload['gross_amount'] ?? $donation->amount);
            $payment->gross_amount = $gross;
            // If fee unknown, keep 0 for now
            $payment->fee_amount = $payment->fee_amount ?? 0;
            $payment->net_amount = $gross - (float) $payment->fee_amount;
            $resPayload = $payment->payload_res_json ?? [];
            $resPayload['last_notification'] = $payload;
            $payment->payload_res_json = $resPayload;
            $payment->save();
        }

        // Update donation status
        $donation->status = $map['status'];
        $donation->paid_at = $map['paid_at'];
        $meta = $donation->meta_json ?? [];
        $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
            'notification' => $payload,
            'last_updated_at' => now()->toISOString(),
        ];
        $donation->meta_json = $meta;
        $donation->save();

        // If paid, credit campaign wallet and update campaign raised amount
        if ($donation->status === 'paid') {
            $campaign = $donation->campaign()->first();
            if ($campaign) {
                // credit to campaign's wallet
                $ownerType = \App\Models\Campaign::class;
                $ownerId = $campaign->id;
                $wallet = Wallet::firstOrCreate(
                    ['owner_type' => $ownerType, 'owner_id' => $ownerId],
                    ['balance' => 0, 'settings_json' => null]
                );

                $amount = $payment?->net_amount ?? $donation->amount;
                $wallet->balance = (float) $wallet->balance + (float) $amount;
                $wallet->save();

                LedgerEntry::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'source_type' => Donation::class,
                    'source_id' => $donation->id,
                    'memo' => 'Donation ' . $donation->reference,
                    'balance_after' => $wallet->balance,
                    'created_at' => now(),
                ]);

                // Update campaign raised_amount
                $campaign->raised_amount = (float) $campaign->raised_amount + (float) $amount;
                $campaign->save();
            }

            // Optionally send WA message on payment success (only once overall)
            try {
                $svc = new WaService();
                $cfg = $svc->getConfig();
                if ((bool)($cfg['send_enabled'] ?? false) && ! empty($cfg['send_client_id'])) {
                    $orgName = $donation->campaign?->organization?->name ?? config('app.name');
                    $ptype = (string) data_get($donation->meta_json, 'payment_type', 'automatic');
                    $payUrl = route('donation.pay', ['reference' => $donation->reference]);
                    if ($ptype === 'manual') {
                        $payUrl = route('donation.manual', ['reference' => $donation->reference]);
                    }
                    $vars = [
                        'donor_name' => (string)($donation->donor_name ?? ''),
                        'donor_phone' => (string)($donation->donor_phone ?? ''),
                        'donor_email' => (string)($donation->donor_email ?? ''),
                        'amount' => number_format((float)$donation->amount, 0, ',', '.'),
                        'amount_raw' => (string)$donation->amount,
                        'campaign_title' => (string)($donation->campaign?->title ?? ''),
                        'campaign_url' => $donation->campaign ? route('campaign.show', $donation->campaign->slug) : '',
                        'pay_url' => $payUrl,
                        'donation_reference' => (string)$donation->reference,
                        'organization_name' => (string)$orgName,
                    ];
                    // Use paid/success template
                    $template = (string) ($cfg['message_template_paid'] ?? ($cfg['message_template'] ?? ''));
                    if ($template !== '' && ! empty($donation->donor_phone)) {
                        $already = (bool) (data_get($donation->meta_json, 'wa.sent')
                                   || data_get($donation->meta_json, 'wa.sent_initiated')
                                   || data_get($donation->meta_json, 'wa.sent_paid'));
                        if (! $already) {
                            $message = $svc->renderTemplate($template, $vars);
                            $ok = $svc->sendText((string)$donation->donor_phone, $message);
                            if ($ok) {
                                $meta = $donation->meta_json ?? [];
                                $meta['wa'] = ($meta['wa'] ?? []) + [
                                    'sent' => now()->toISOString(),
                                    'sent_event' => 'paid',
                                ];
                                $donation->meta_json = $meta;
                                $donation->save();
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore WA failures silently
            }
        }

        // Mark webhook processed
        $webhook->processed = true;
        $webhook->processed_at = now();
        $webhook->save();

        return response()->json(['message' => 'ok']);
    }
}

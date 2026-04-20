<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Models\LedgerEntry;
use App\Models\WebhookEvent;
use App\Services\Payments\DuitkuService;
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

        $channel = PaymentChannel::firstOrCreate(
            ['code' => 'MANUAL'],
            ['name' => 'Manual', 'active' => true]
        );
        $method = PaymentMethod::firstOrCreate(
            ['provider' => 'manual', 'method_code' => 'transfer'],
            ['channel_id' => $channel->id, 'config_json' => null, 'active' => true, 'created_at' => now()]
        );

        $payment = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();
        if (! $payment || $payment->payment_method_id !== $method->id) {
            $payment = Payment::create([
                'transaction_id'    => $donation->id,
                'payment_method_id' => $method->id,
                'provider_txn_id'   => $donation->reference,
                'provider_status'   => 'pending',
                'manual_status'     => 'pending',
                'gross_amount'      => $donation->amount,
                'fee_amount'        => 0,
                'net_amount'        => $donation->amount,
            ]);
        }

        $org  = $donation->campaign?->organization;
        $meta = $org?->meta_json ?? [];
        $bank = [
            'name'           => data_get($meta, 'payments.manual.bank_name'),
            'account_name'   => data_get($meta, 'payments.manual.bank_account_name'),
            'account_number' => data_get($meta, 'payments.manual.bank_account_number'),
            'instructions'   => data_get($meta, 'payments.manual.instructions'),
            'qr_path'        => data_get($meta, 'payments.manual.qr_path'),
        ];

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
            'payment'  => $payment,
            'bank'     => $bank,
            'qrUrl'    => $qrUrl,
        ]);
    }

    public function submitManual(Request $request, string $reference)
    {
        $donation = Donation::query()->with('campaign')->where('reference', $reference)->firstOrFail();

        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'note'  => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();
        if (! $payment) {
            return back()->withErrors(['proof' => 'Transaksi manual tidak ditemukan.'])->withInput();
        }

        $path = $request->file('proof')->store('manual-payments/proofs', ['disk' => media_disk(), 'visibility' => 'private']);
        $payment->manual_proof_path = $path;
        $payment->manual_note       = $data['note'] ?? null;
        $payment->manual_status     = 'pending';
        $payment->save();

        return redirect()->route('donation.thanks', ['reference' => $donation->reference]);
    }

    /**
     * Create Duitku transaction and redirect user to paymentUrl.
     */
    public function pay(Request $request, string $reference)
    {
        $donation = Donation::query()
            ->with(['campaign:id,title,slug,organization_id', 'campaign.organization:id,meta_json'])
            ->where('reference', $reference)
            ->firstOrFail();

        // If already redirected (paymentUrl stored), just redirect again
        $meta        = $donation->meta_json ?? [];
        $paymentUrl  = $meta['duitku']['payment_url'] ?? null;

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

            $baseAmount = max(1, (int) round((float) $donation->amount));

            $itemName = Str::limit('Donasi: ' . ($donation->campaign?->title ?? 'Campaign'), 255, '');
            $items = [[
                'name'     => $itemName,
                'price'    => $baseAmount,
                'quantity' => 1,
            ]];

            $res = $duitku->createTransactionForDonation($donation, [
                'override_gross' => $baseAmount,
                'item_details'   => $items,
            ]);

            $paymentUrl = $res['paymentUrl'];

            // Store to meta
            $meta['duitku'] = ($meta['duitku'] ?? []) + [
                'payment_url'  => $paymentUrl,
                'reference'    => $res['reference'] ?? null,
                'order_id'     => $res['merchantOrderId'],
                'gross_amount' => $res['grossAmount'],
            ];
            $donation->meta_json = $meta;
            $donation->save();

            // Create payment record
            $existing = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();
            if (! $existing) {
                Payment::create([
                    'transaction_id'    => $donation->id,
                    'payment_method_id' => $method->id,
                    'provider_txn_id'   => $res['merchantOrderId'],
                    'provider_status'   => 'initiated',
                    'gross_amount'      => $res['grossAmount'],
                    'fee_amount'        => 0,
                    'net_amount'        => $res['grossAmount'],
                    'payload_req_json'  => $res['request'] ?? null,
                    'payload_res_json'  => $res['response'] ?? null,
                ]);
            }
        }

        return redirect()->away($paymentUrl);
    }

    /** Unified Duitku webhook for donations. */
    public function notify(Request $request)
    {
        // Duitku sends form-urlencoded POST
        $merchantCode    = $request->input('merchantCode', '');
        $amount          = $request->input('amount', '');
        $merchantOrderId = $request->input('merchantOrderId', '');
        $resultCode      = $request->input('resultCode', '');
        $reference       = $request->input('reference', '');
        $signature       = $request->input('signature', '');

        $duitku = new DuitkuService();

        // Validate signature
        if (! $duitku->validateCallbackSignature($merchantCode, $amount, $merchantOrderId, $signature)) {
            return response('Bad Signature', 403);
        }

        // Try to find a Donation with this reference first
        $donation = Donation::query()->where('reference', $merchantOrderId)->first();
        if ($donation) {
            return $this->processDonationCallback($duitku, $donation, $resultCode, $reference, $amount, $merchantOrderId, $request->all());
        }

        // Try Order (order references can have suffix like -RHHMMSS-XXX)
        // We strip the suffix to find the base order reference
        $baseOrderRef = preg_replace('/-R\d{6}-[A-Z0-9]{3}$/', '', $merchantOrderId);
        $order = \App\Models\Order::query()
            ->where('reference', $merchantOrderId)
            ->orWhere('reference', $baseOrderRef)
            ->first();

        if ($order) {
            return $this->processOrderCallback($order, $resultCode, $merchantOrderId, $request->all());
        }

        return response('Order/Donation not found', 404);
    }

    protected function processDonationCallback(
        DuitkuService $duitku,
        Donation $donation,
        string $resultCode,
        string $reference,
        string $amount,
        string $merchantOrderId,
        array $payload
    ) {
        $status = $duitku->mapResultCode($resultCode);

        // Find latest payment
        $payment = Payment::query()->where('transaction_id', $donation->id)->latest('id')->first();

        // Record webhook
        $webhook = WebhookEvent::create([
            'payment_id'   => $payment?->id,
            'event_type'   => 'duitku_callback_' . $resultCode,
            'raw_body_json'=> $payload,
            'signature'    => $payload['signature'] ?? null,
            'received_at'  => now(),
            'processed'    => false,
        ]);

        if ($payment) {
            $payment->provider_txn_id  = $reference ?: $merchantOrderId;
            $payment->provider_status  = $status;
            $payment->gross_amount     = (float) $amount;
            $payment->net_amount       = (float) $amount;
            $resPayload = $payment->payload_res_json ?? [];
            $resPayload['last_notification'] = $payload;
            $payment->payload_res_json = $resPayload;
            $payment->save();
        }

        $donation->status  = $status;
        $donation->paid_at = ($status === 'paid') ? now() : null;
        $meta = $donation->meta_json ?? [];
        $meta['duitku'] = ($meta['duitku'] ?? []) + [
            'last_callback'    => $payload,
            'last_updated_at'  => now()->toISOString(),
        ];
        $donation->meta_json = $meta;
        $donation->save();

        // Credit wallet on paid
        if ($status === 'paid') {
            $campaign = $donation->campaign()->first();
            if ($campaign) {
                $wallet = Wallet::firstOrCreate(
                    ['owner_type' => \App\Models\Campaign::class, 'owner_id' => $campaign->id],
                    ['balance' => 0, 'settings_json' => null]
                );
                $net = $payment?->net_amount ?? $donation->amount;
                $wallet->balance = (float) $wallet->balance + (float) $net;
                $wallet->save();

                LedgerEntry::create([
                    'wallet_id'     => $wallet->id,
                    'type'          => 'credit',
                    'amount'        => $net,
                    'source_type'   => Donation::class,
                    'source_id'     => $donation->id,
                    'memo'          => 'Donation ' . $donation->reference,
                    'balance_after' => $wallet->balance,
                    'created_at'    => now(),
                ]);
            }

            // Optional WA notification
            try {
                $svc = new WaService();
                $cfg = $svc->getConfig();
                if ((bool)($cfg['send_enabled'] ?? false) && ! empty($cfg['send_client_id'])) {
                    $orgName  = $donation->campaign?->organization?->name ?? config('app.name');
                    $template = (string) ($cfg['message_template_paid'] ?? ($cfg['message_template'] ?? ''));
                    if ($template !== '' && ! empty($donation->donor_phone)) {
                        $already = (bool) (data_get($donation->meta_json, 'wa.sent')
                                   || data_get($donation->meta_json, 'wa.sent_paid'));
                        if (! $already) {
                            $vars = [
                                'donor_name'       => (string)($donation->donor_name ?? ''),
                                'donor_phone'      => (string)($donation->donor_phone ?? ''),
                                'donor_email'      => (string)($donation->donor_email ?? ''),
                                'amount'           => number_format((float)$donation->amount, 0, ',', '.'),
                                'amount_raw'       => (string)$donation->amount,
                                'campaign_title'   => (string)($donation->campaign?->title ?? ''),
                                'campaign_url'     => $donation->campaign ? route('campaign.show', $donation->campaign->slug) : '',
                                'donation_reference' => (string)$donation->reference,
                                'organization_name'=> (string)$orgName,
                            ];
                            $message = $svc->renderTemplate($template, $vars);
                            $ok = $svc->sendText((string)$donation->donor_phone, $message);
                            if ($ok) {
                                $m = $donation->meta_json ?? [];
                                $m['wa'] = ($m['wa'] ?? []) + ['sent' => now()->toISOString(), 'sent_event' => 'paid'];
                                $donation->meta_json = $m;
                                $donation->save();
                            }
                        }
                    }
                }
            } catch (\Throwable) { /* silent */ }
        }

        $webhook->processed    = true;
        $webhook->processed_at = now();
        $webhook->save();

        return response('SUCCESS', 200);
    }

    protected function processOrderCallback(\App\Models\Order $order, string $resultCode, string $merchantOrderId, array $payload)
    {
        $duitku = new DuitkuService();
        $status = $duitku->mapResultCode($resultCode);

        if ($status === 'paid') {
            $order->status  = 'paid';
            $order->paid_at = now();
            try { \App\Services\TicketIssuer::issueForOrder($order); } catch (\Throwable) {}
        } elseif ($status === 'failed') {
            $order->status = 'failed';
        } else {
            $order->status = 'pending';
        }

        $meta = $order->meta_json ?? [];
        $meta['duitku'] = ($meta['duitku'] ?? []) + [
            'last_callback'   => $payload,
            'last_updated_at' => now()->toISOString(),
        ];
        $order->meta_json = $meta;
        $order->save();

        return response('SUCCESS', 200);
    }

    // ── Keep method-choose route working (now just goes to pay) ────────────
    public function methods(Request $request, string $reference)
    {
        // Simplified: redirect straight to pay page (Duitku shows all methods)
        return redirect()->route('donation.pay', ['reference' => $reference]);
    }

    public function choose(Request $request, string $reference)
    {
        return redirect()->route('donation.pay', ['reference' => $reference]);
    }
}

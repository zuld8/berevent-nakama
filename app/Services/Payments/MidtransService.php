<?php

namespace App\Services\Payments;

use App\Models\Donation;
use App\Models\Order;
use App\Models\Organization;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MidtransService
{
    public function __construct(
        protected ?Organization $org = null,
    ) {
        $this->org = $this->org ?: Organization::query()->first();
    }

    public function isProduction(): bool
    {
        $org = $this->org;
        $meta = $org?->meta_json ?? [];
        return (bool) Arr::get($meta, 'payments.midtrans.is_production', (bool) env('MIDTRANS_IS_PRODUCTION', false));
    }

    public function serverKey(): string
    {
        $meta = $this->org?->meta_json ?? [];
        return (string) Arr::get($meta, 'payments.midtrans.server_key', env('MIDTRANS_SERVER_KEY', ''));
    }

    public function clientKey(): string
    {
        $meta = $this->org?->meta_json ?? [];
        return (string) Arr::get($meta, 'payments.midtrans.client_key', env('MIDTRANS_CLIENT_KEY', ''));
    }

    public function merchantId(): ?string
    {
        $meta = $this->org?->meta_json ?? [];
        return Arr::get($meta, 'payments.midtrans.merchant_id');
    }

    public function snapJsUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    protected function snapApiBase(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    public function createSnapTransaction(Donation $donation, array $options = []): array
    {
        $campaign = $donation->campaign()->first();
        $orderId = $donation->reference;
        // Ensure integer rupiah value
        $baseAmount = max(1, (int) round((float) $donation->amount));
        $overrideGross = $options['override_gross'] ?? null;
        $grossAmount = $overrideGross ? max(1, (int) round((float) $overrideGross)) : $baseAmount;
        // Midtrans item name limit is 50 chars
        $itemName = Str::limit('Donasi: ' . ($campaign?->title ?? 'Campaign'), 50, '');

        $itemDetails = $options['item_details'] ?? null;
        if (!is_array($itemDetails) || empty($itemDetails)) {
            $itemDetails = [[
                'id' => 'donation',
                'price' => $baseAmount,
                'quantity' => 1,
                'name' => $itemName,
            ]];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $donation->donor_name ?: 'Donatur',
                'email' => $donation->donor_email ?: 'donatur@example.test',
                'phone' => $donation->donor_phone ?: null,
            ],
            'callbacks' => [
                'finish' => route('donation.thanks', ['reference' => $donation->reference]),
            ],
            'credit_card' => [
                'secure' => true,
            ],
        ];

        // Allow narrowing to a specific payment method (e.g. 'bca_va', 'qris')
        $enabled = $options['enabled_payments'] ?? null;
        if (is_string($enabled) && $enabled !== '') {
            $enabled = [$enabled];
        }
        if (is_array($enabled) && ! empty($enabled)) {
            $payload['enabled_payments'] = array_values($enabled);
        }

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($this->snapApiBase() . '/transactions', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Gagal membuat transaksi Midtrans: ' . $response->body());
        }

        $data = $response->json();
        // data: token, redirect_url
        return [
            'token' => $data['token'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
            'request' => $payload,
            'response' => $data,
        ];
    }

    public function createSnapTransactionForOrder(Order $order, array $options = []): array
    {
        $orderId = $options['override_order_id'] ?? $order->reference;
        $grossAmount = max(1, (int) round((float) ($options['override_gross'] ?? $order->total_amount)));
        $itemDetails = $options['item_details'] ?? [];
        if (empty($itemDetails)) {
            $itemDetails = $order->items->map(function ($it) {
                return [
                    'id' => 'event_' . $it->event_id,
                    'price' => max(0, (int) round((float) $it->unit_price)),
                    'quantity' => (int) $it->qty,
                    'name' => Str::limit($it->title, 50, ''),
                ];
            })->values()->all();
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => auth()->user()?->name ?: 'Customer',
                'email' => auth()->user()?->email ?: 'customer@example.test',
            ],
            'callbacks' => [
                'finish' => route('order.thanks', ['reference' => $order->reference]),
            ],
            'credit_card' => [ 'secure' => true ],
        ];

        $enabled = $options['enabled_payments'] ?? null;
        if (is_string($enabled) && $enabled !== '') { $enabled = [$enabled]; }
        if (is_array($enabled) && ! empty($enabled)) { $payload['enabled_payments'] = array_values($enabled); }

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->serverKey(), '')
            ->withHeaders(['Accept' => 'application/json','Content-Type' => 'application/json'])
            ->post($this->snapApiBase() . '/transactions', $payload);
        if (! $response->successful()) {
            throw new \RuntimeException('Gagal membuat transaksi Midtrans: ' . $response->body());
        }
        $data = $response->json();
        return [
            'token' => $data['token'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
            'request' => $payload,
            'response' => $data,
        ];
    }

    public function validateNotificationSignature(array $payload): bool
    {
        // signature_key = sha512(order_id + status_code + gross_amount + server_key)
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = $this->serverKey();

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($expected, $signature);
    }

    public function mapTransactionStatusToDonation(array $payload): array
    {
        // Map Midtrans transaction_status to donation status
        $status = $payload['transaction_status'] ?? null;
        $fraud = $payload['fraud_status'] ?? null;

        $donationStatus = 'initiated';
        $paidAt = null;

        if ($status === 'capture') {
            $donationStatus = $fraud === 'accept' ? 'paid' : 'initiated';
            if ($donationStatus === 'paid') {
                $paidAt = now();
            }
        } elseif ($status === 'settlement') {
            $donationStatus = 'paid';
            $paidAt = now();
        } elseif (in_array($status, ['deny', 'cancel', 'expire'])) {
            $donationStatus = 'failed';
        } elseif ($status === 'pending') {
            $donationStatus = 'initiated';
        }

        return [
            'status' => $donationStatus,
            'paid_at' => $paidAt,
        ];
    }
}

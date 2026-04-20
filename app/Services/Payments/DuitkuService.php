<?php

namespace App\Services\Payments;

use App\Models\Donation;
use App\Models\Order;
use App\Models\Organization;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DuitkuService
{
    public function __construct(
        protected ?Organization $org = null,
    ) {
        $this->org = $this->org ?: Organization::query()->first();
    }

    public function isProduction(): bool
    {
        $meta = $this->org?->meta_json ?? [];
        return (bool) Arr::get($meta, 'payments.duitku.is_production', (bool) env('DUITKU_IS_PRODUCTION', true));
    }

    public function merchantCode(): string
    {
        $meta = $this->org?->meta_json ?? [];
        return (string) Arr::get($meta, 'payments.duitku.merchant_code', env('DUITKU_MERCHANT_CODE', ''));
    }

    public function apiKey(): string
    {
        $meta = $this->org?->meta_json ?? [];
        return (string) Arr::get($meta, 'payments.duitku.api_key', env('DUITKU_API_KEY', ''));
    }

    protected function baseUrl(): string
    {
        return $this->isProduction()
            ? 'https://passport.duitku.com/webapi/api/merchant'
            : 'https://sandbox.duitku.com/webapi/api/merchant';
    }

    /**
     * Signature for create transaction = md5(merchantCode + merchantOrderId + paymentAmount + apiKey)
     */
    protected function makeSignature(string $merchantOrderId, int $paymentAmount): string
    {
        return md5($this->merchantCode() . $merchantOrderId . $paymentAmount . $this->apiKey());
    }

    /**
     * Validate callback signature = md5(merchantCode + amount + merchantOrderId + apiKey)
     */
    public function validateCallbackSignature(string $merchantCode, string $amount, string $merchantOrderId, string $signature): bool
    {
        $expected = md5($merchantCode . $amount . $merchantOrderId . $this->apiKey());
        return hash_equals($expected, $signature);
    }

    /**
     * Get active payment methods from Duitku API for a given amount.
     * Signature: sha256(merchantCode + paymentAmount + datetime + apiKey)
     * Returns array of [ paymentMethod, paymentName, paymentImage, totalFee ]
     */
    public function getPaymentMethods(int $amount = 10000): array
    {
        try {
            $datetime  = now()->format('Y-m-d H:i:s');
            $signature = hash('sha256', $this->merchantCode() . $amount . $datetime . $this->apiKey());

            $endpoint = $this->isProduction()
                ? 'https://passport.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod'
                : 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, [
                    'merchantcode' => $this->merchantCode(),
                    'amount'       => $amount,
                    'datetime'     => $datetime,
                    'signature'    => $signature,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['responseCode'] ?? null) === '00' && !empty($data['paymentFee'])) {
                    return $data['paymentFee'];
                }
            }
        } catch (\Throwable) { /* fall through to static list */ }

        // Static fallback list (common Indonesian payment methods)
        return [
            ['paymentMethod' => 'BCA', 'paymentName' => 'BCA Virtual Account',      'paymentImage' => 'https://images.duitku.com/hotlink-ok/BCA.PNG',     'totalFee' => '0'],
            ['paymentMethod' => 'BNI', 'paymentName' => 'BNI Virtual Account',      'paymentImage' => 'https://images.duitku.com/hotlink-ok/BNI.PNG',     'totalFee' => '0'],
            ['paymentMethod' => 'BRI', 'paymentName' => 'BRI Virtual Account',      'paymentImage' => 'https://images.duitku.com/hotlink-ok/BRI.PNG',     'totalFee' => '0'],
            ['paymentMethod' => 'M2',  'paymentName' => 'Mandiri Virtual Account',  'paymentImage' => 'https://images.duitku.com/hotlink-ok/M2.PNG',      'totalFee' => '0'],
            ['paymentMethod' => 'BT',  'paymentName' => 'Permata Virtual Account',  'paymentImage' => 'https://images.duitku.com/hotlink-ok/PERMATA.PNG', 'totalFee' => '0'],
            ['paymentMethod' => 'QR',  'paymentName' => 'QRIS',                     'paymentImage' => 'https://images.duitku.com/hotlink-ok/QRIS.PNG',    'totalFee' => '0'],
            ['paymentMethod' => 'DA',  'paymentName' => 'DANA',                     'paymentImage' => 'https://images.duitku.com/hotlink-ok/DA.PNG',      'totalFee' => '0'],
            ['paymentMethod' => 'OV',  'paymentName' => 'OVO',                      'paymentImage' => 'https://images.duitku.com/hotlink-ok/OV.PNG',      'totalFee' => '0'],
            ['paymentMethod' => 'SA',  'paymentName' => 'ShopeePay',               'paymentImage' => 'https://images.duitku.com/hotlink-ok/SA.PNG',      'totalFee' => '0'],
        ];
    }


    /**
     * Create Duitku transaction for a Donation.
     * Returns: [ paymentUrl, reference, statusCode, statusMessage, vaNumber, qrString ]
     */
    public function createTransactionForDonation(Donation $donation, array $options = []): array
    {
        $campaign = $donation->campaign()->first();
        $merchantOrderId = $donation->reference;
        $paymentAmount = max(1, (int) round((float) $options['override_gross'] ?? (float) $donation->amount));
        $productDetails = Str::limit('Donasi: ' . ($campaign?->title ?? 'Campaign'), 255, '');

        $user = $donation->donor_name ?: 'Donatur';
        $email = $donation->donor_email ?: 'donatur@example.test';
        $phone = $donation->donor_phone ?: null;

        $callbackUrl = route('duitku.notify');
        $returnUrl = route('donation.thanks', ['reference' => $donation->reference]);

        $signature = $this->makeSignature($merchantOrderId, $paymentAmount);

        $payload = [
            'merchantCode'    => $this->merchantCode(),
            'paymentAmount'   => $paymentAmount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails'  => $productDetails,
            'email'           => $email,
            'phoneNumber'     => $phone,
            'customerVaName'  => $user,
            'callbackUrl'     => $callbackUrl,
            'returnUrl'       => $returnUrl,
            'signature'       => $signature,
            'expiryPeriod'    => 1440, // 24 jam
        ];

        // Optional: narrow to specific payment method
        if (!empty($options['paymentMethod'])) {
            $payload['paymentMethod'] = $options['paymentMethod'];
        }

        // Item details
        $itemDetails = $options['item_details'] ?? null;
        if (is_array($itemDetails) && !empty($itemDetails)) {
            $payload['itemDetails'] = $itemDetails;
        } else {
            $payload['itemDetails'] = [[
                'name'     => $productDetails,
                'price'    => $paymentAmount,
                'quantity' => 1,
            ]];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl() . '/v2/inquiry', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Gagal membuat transaksi Duitku: ' . $response->body());
        }

        $data = $response->json();
        if (($data['statusCode'] ?? null) !== '00') {
            throw new \RuntimeException('Duitku error: ' . ($data['statusMessage'] ?? $response->body()));
        }

        return [
            'paymentUrl'    => $data['paymentUrl'] ?? null,
            'reference'     => $data['reference'] ?? null,
            'vaNumber'      => $data['vaNumber'] ?? null,
            'qrString'      => $data['qrString'] ?? null,
            'statusCode'    => $data['statusCode'] ?? null,
            'statusMessage' => $data['statusMessage'] ?? null,
            'merchantOrderId' => $merchantOrderId,
            'grossAmount'   => $paymentAmount,
            'request'       => $payload,
            'response'      => $data,
        ];
    }

    /**
     * Create Duitku transaction for an Order.
     */
    public function createTransactionForOrder(Order $order, array $options = []): array
    {
        $merchantOrderId = $options['override_order_id'] ?? $order->reference;
        $paymentAmount = max(1, (int) round((float) ($options['override_gross'] ?? $order->total_amount)));
        $productDetails = Str::limit('Pesanan Event', 255, '');

        $user = auth()->user()?->name ?: 'Customer';
        $email = auth()->user()?->email ?: 'customer@example.test';

        $callbackUrl = route('duitku.notify');
        $returnUrl = route('order.thanks', ['reference' => $order->reference]);

        $signature = $this->makeSignature($merchantOrderId, $paymentAmount);

        // Build itemDetails
        $itemDetails = $options['item_details'] ?? [];
        if (empty($itemDetails)) {
            $itemDetails = $order->items->map(function ($it) {
                return [
                    'name'     => Str::limit($it->title, 255, ''),
                    'price'    => max(0, (int) round((float) $it->unit_price)),
                    'quantity' => (int) $it->qty,
                ];
            })->values()->all();
        }

        $payload = [
            'merchantCode'    => $this->merchantCode(),
            'paymentAmount'   => $paymentAmount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails'  => $productDetails,
            'email'           => $email,
            'customerVaName'  => $user,
            'itemDetails'     => $itemDetails,
            'callbackUrl'     => $callbackUrl,
            'returnUrl'       => $returnUrl,
            'signature'       => $signature,
            'expiryPeriod'    => 1440,
        ];

        if (!empty($options['paymentMethod'])) {
            $payload['paymentMethod'] = $options['paymentMethod'];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl() . '/v2/inquiry', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Gagal membuat transaksi Duitku: ' . $response->body());
        }

        $data = $response->json();
        if (($data['statusCode'] ?? null) !== '00') {
            throw new \RuntimeException('Duitku error: ' . ($data['statusMessage'] ?? $response->body()));
        }

        return [
            'paymentUrl'    => $data['paymentUrl'] ?? null,
            'reference'     => $data['reference'] ?? null,
            'vaNumber'      => $data['vaNumber'] ?? null,
            'qrString'      => $data['qrString'] ?? null,
            'statusCode'    => $data['statusCode'] ?? null,
            'statusMessage' => $data['statusMessage'] ?? null,
            'merchantOrderId' => $merchantOrderId,
            'grossAmount'   => $paymentAmount,
            'request'       => $payload,
            'response'      => $data,
        ];
    }

    /**
     * Map Duitku resultCode to donation/order status
     * resultCode "00" = SUCCESS, others = FAILED/PENDING
     */
    public function mapResultCode(string $resultCode): string
    {
        return match ($resultCode) {
            '00'    => 'paid',
            '01'    => 'initiated', // pending / waiting
            default => 'failed',
        };
    }
}

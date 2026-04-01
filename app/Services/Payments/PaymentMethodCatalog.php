<?php

namespace App\Services\Payments;

use App\Models\Organization;
use App\Models\PaymentChannel;
use App\Models\PaymentMethod;
use Illuminate\Support\Arr;

class PaymentMethodCatalog
{
    /**
     * Default Midtrans methods and fees (fallback when admin not set).
     * method_code follows Midtrans enabled_payments values.
     */
    public function midtransDefaults(): array
    {
        return [
            'bca_va' => [
                'name' => 'BCA Virtual Account',
                'logo' => asset('images/payments/bca.png'),
                'fee'  => ['type' => 'flat', 'value' => 4000],
            ],
            'bri_va' => [
                'name' => 'BRI Virtual Account',
                'logo' => asset('images/payments/bri.png'),
                'fee'  => ['type' => 'flat', 'value' => 4000],
            ],
            'qris' => [
                'name' => 'QRIS',
                'logo' => asset('images/payments/qris.png'),
                'fee'  => ['type' => 'percent', 'value' => 0.7],
            ],
            'bni_va' => [
                'name' => 'BNI Virtual Account',
                'logo' => asset('images/payments/bni.png'),
                'fee'  => ['type' => 'flat', 'value' => 4000],
            ],
            // Midtrans uses 'echannel' for Mandiri Bill Payment
            'echannel' => [
                'name' => 'Mandiri Bill Payment',
                'logo' => asset('images/payments/mandiri.png'),
                'fee'  => ['type' => 'flat', 'value' => 4000],
            ],
        ];
    }

    /** Ensure channel + default methods exist in DB (first run bootstrap). */
    public function ensureMidtransRecords(): void
    {
        $channel = PaymentChannel::firstOrCreate(
            ['code' => 'MIDTRANS'],
            ['name' => 'Midtrans', 'active' => true]
        );

        $defaults = $this->midtransDefaults();
        foreach ($defaults as $code => $def) {
            PaymentMethod::firstOrCreate(
                ['provider' => 'midtrans', 'method_code' => $code],
                [
                    'channel_id' => $channel->id,
                    'config_json' => null, // admin can override later
                    'active' => true,
                    'created_at' => now(),
                ],
            );
        }
    }

    /**
     * Get active Midtrans methods merged with admin overrides.
     * Each item: id(code), name, logo, provider, active, fee_type, fee_value.
     */
    public function activeMidtrans(): array
    {
        $this->ensureMidtransRecords();

        $defaults = $this->midtransDefaults();
        $records = PaymentMethod::query()
            ->where('provider', 'midtrans')
            ->orderBy('method_code')
            ->get();

        $out = [];
        foreach ($records as $row) {
            $code = $row->method_code;
            $def = $defaults[$code] ?? null;
            if (! $def) continue;

            if (! $row->active) continue; // on/off controlled by DB

            $cfg = $row->config_json ?? [];
            $feeType = Arr::get($cfg, 'fee_type');
            $feeValue = Arr::get($cfg, 'fee_value');
            if (! $feeType) { // fall back to gateway default
                $feeType = $def['fee']['type'];
                $feeValue = $def['fee']['value'];
            }

            $out[] = [
                'id' => $code,
                'name' => $def['name'],
                'logo' => $def['logo'],
                'provider' => 'Midtrans',
                'fee_type' => $feeType,
                'fee_value' => $feeValue,
            ];
        }

        return $out;
    }

    /** Render fee text for display. */
    public function feeText(array $m): string
    {
        if (($m['fee_type'] ?? '') === 'percent') {
            $v = (float) $m['fee_value'];
            $v = rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
            return "Admin fee {$v}%";
        }
        $v = (float) ($m['fee_value'] ?? 0);
        return 'Admin fee Rp' . number_format($v, 0, ',', '.');
    }

    /** Find active method by id/code. */
    public function findActive(string $id): ?array
    {
        foreach ($this->activeMidtrans() as $m) {
            if ($m['id'] === $id) return $m;
        }
        return null;
    }

    /**
     * Compute fee amount for a given donation amount and method id.
     * Returns integer rupiah amount and details.
     */
    public function computeFee(int|float $amount, string $methodId): array
    {
        $m = $this->findActive($methodId);
        if (! $m) return ['amount' => 0, 'type' => null, 'value' => null];

        $type = (string) ($m['fee_type'] ?? 'flat');
        $value = (float) ($m['fee_value'] ?? 0);
        $fee = 0.0;
        if ($type === 'percent') {
            $fee = ((float) $amount) * ($value / 100.0);
        } else {
            $fee = $value;
        }
        $fee = (int) round($fee); // integer rupiah
        return ['amount' => $fee, 'type' => $type, 'value' => $value];
    }
}

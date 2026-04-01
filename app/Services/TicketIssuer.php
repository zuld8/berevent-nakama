<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Str;

class TicketIssuer
{
    public static function issueForOrder(Order $order): void
    {
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $existing = Ticket::query()->where('order_item_id', $item->id)->count();
            $toMake = max(0, (int)$item->qty - (int)$existing);
            for ($i = 0; $i < $toMake; $i++) {
                $code = static::uniqueCode();
                Ticket::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'event_id' => $item->event_id,
                    'code' => $code,
                    'status' => 'issued',
                ]);
            }
        }
    }

    protected static function uniqueCode(): string
    {
        do {
            $code = 'TKT-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Ticket::query()->where('code', $code)->exists());
        return $code;
    }
}


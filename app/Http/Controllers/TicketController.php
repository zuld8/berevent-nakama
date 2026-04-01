<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request, string $reference)
    {
        $order = Order::query()
            ->with(['items', 'items.event', 'items' => function($q){ $q->with('event'); }])
            ->where('reference', $reference)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $tickets = \App\Models\Ticket::query()
            ->where('order_id', $order->id)
            ->with(['event' => function($q){
                $q->with(['materials' => function($mq){ $mq->orderBy('date_at')->orderBy('id'); }, 'materials.mentor']);
            }])
            ->orderBy('id')
            ->get();

        return view('order.tickets', [
            'order' => $order,
            'tickets' => $tickets,
        ]);
    }

    public function print(Request $request, string $reference)
    {
        $user = $request->user();
        if (! $user) { return redirect()->route('login'); }
        $order = \App\Models\Order::query()
            ->with(['items','items.event'])
            ->where('reference', $reference)
            ->where('user_id', $user->id)
            ->firstOrFail();
        $tickets = \App\Models\Ticket::query()
            ->where('order_id', $order->id)
            ->with(['event'])
            ->orderBy('id')
            ->get();
        // Build inline QR map (data URI) to ensure it appears in PDF
        $qrMap = [];
        if (class_exists(\SimpleSoftwareIO\QrCode\Facade::class) || class_exists('QrCode')) {
            foreach ($tickets as $t) {
                try {
                    $png = (class_exists('QrCode') ? \QrCode::format('png') : \SimpleSoftwareIO\QrCode\Facade::format('png'))
                        ->size(200)->margin(1)->errorCorrection('M')->generate($t->code);
                    $qrMap[$t->code] = 'data:image/png;base64,' . base64_encode($png);
                } catch (\Throwable $e) { /* ignore, fallback below */ }
            }
        }

        // If DOMPDF is installed, stream real PDF; else fallback to print view
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) || class_exists('PDF')) {
            try {
                $view = view('order.tickets-print', ['order' => $order, 'tickets' => $tickets, 'qrMap' => $qrMap])->render();
                $pdf = class_exists('PDF')
                    ? \PDF::loadHTML($view)
                    : \Barryvdh\DomPDF\Facade\Pdf::loadHTML($view);
                // Set A5 portrait as requested
                if (method_exists($pdf, 'setPaper')) {
                    $pdf->setPaper('a5', 'portrait');
                }
                $file = 'tickets-' . $order->reference . '.pdf';
                return $pdf->download($file);
            } catch (\Throwable $e) { /* fallback below */ }
        }

        return view('order.tickets-print', ['order' => $order, 'tickets' => $tickets, 'qrMap' => $qrMap]);
    }

    public function qr(Request $request, string $code)
    {
        $ticket = \App\Models\Ticket::query()->with('order')->where('code', $code)->firstOrFail();
        // Ensure current user owns the ticket's order
        if (!auth()->check() || $ticket->order?->user_id !== auth()->id()) {
            abort(404);
        }
        $size = max(120, min(1024, (int) $request->query('s', 240)));

        // Preferred: server-side QR via simplesoftwareio/simple-qrcode
        if (class_exists(\SimpleSoftwareIO\QrCode\Facade::class) || class_exists('QrCode')) {
            try {
                // Resolve facade class regardless of aliasing
                $qr = class_exists('QrCode') ? \QrCode::format('png') : \SimpleSoftwareIO\QrCode\Facade::format('png');
                $png = $qr->size($size)->margin(1)->errorCorrection('M')->generate($ticket->code);
                return response($png)->header('Content-Type', 'image/png');
            } catch (\Throwable $e) { /* fallback below */ }
        }

        // Fallback: external chart API (temporary until QR library installed)
        $data = urlencode($ticket->code);
        $url = "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chld=M|0&chl={$data}";
        return redirect()->away($url);
    }
}

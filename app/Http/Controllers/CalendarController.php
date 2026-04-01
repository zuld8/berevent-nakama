<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) { return redirect()->route('login'); }

        $startInput = $request->query('start');
        $endInput = $request->query('end');
        $startDate = null; $endDate = null;
        try { if ($startInput) { $startDate = \Illuminate\Support\Carbon::parse($startInput)->startOfDay(); } } catch (\Throwable) {}
        try { if ($endInput) { $endDate = \Illuminate\Support\Carbon::parse($endInput)->endOfDay(); } } catch (\Throwable) {}

        $tickets = Ticket::query()
            ->with(['event'])
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', 'paid'))
            ->get();

        $byEvent = $tickets->groupBy('event_id');
        $eventIds = $byEvent->keys()->all();
        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->when($startDate || $endDate, function ($q) use ($startDate, $endDate) {
                $q->where(function ($qq) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $qq->where(function ($w) use ($endDate) { $w->whereNull('start_date')->orWhere('start_date', '<=', $endDate); })
                           ->where(function ($w) use ($startDate) { $w->whereNull('end_date')->orWhere('end_date', '>=', $startDate); });
                    } elseif ($startDate) {
                        $qq->where(function ($w) use ($startDate) { $w->whereNull('end_date')->orWhere('end_date', '>=', $startDate); });
                    } else { // only endDate
                        $qq->where(function ($w) use ($endDate) { $w->whereNull('start_date')->orWhere('start_date', '<=', $endDate); });
                    }
                });
            })
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END, start_date ASC')
            ->get();

        $items = $events->map(function ($e) use ($byEvent) {
            $tks = ($byEvent->get($e->id) ?? collect());
            $count = $tks->count();
            $codes = $tks->pluck('code')->values()->all();
            return ['event' => $e, 'ticketCount' => $count, 'tickets' => $codes];
        });

        return view('calendar.index', [
            'items' => $items,
            'start' => $startInput,
            'end' => $endInput,
        ]);
    }

    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        if (! $user) { return redirect()->route('login'); }

        $event->load(['materials' => function ($q) { $q->orderBy('date_at')->orderBy('id'); }, 'materials.mentor']);
        $tickets = Ticket::query()
            ->where('event_id', $event->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', 'paid'))
            ->orderBy('id')
            ->get();

        return view('calendar.show', [
            'event' => $event,
            'tickets' => $tickets,
        ]);
    }

    public function print(Request $request, Event $event)
    {
        $user = $request->user();
        if (! $user) { return redirect()->route('login'); }
        $event->load(['materials' => function ($q) { $q->orderBy('date_at')->orderBy('id'); }]);
        $tickets = \App\Models\Ticket::query()
            ->where('event_id', $event->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', 'paid'))
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
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // If DOMPDF is installed, stream real PDF; else fallback to print view
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) || class_exists('PDF')) {
            try {
                $view = view('calendar.print', ['event' => $event, 'tickets' => $tickets, 'qrMap' => $qrMap])->render();
                $pdf = class_exists('PDF')
                    ? \PDF::loadHTML($view)
                    : \Barryvdh\DomPDF\Facade\Pdf::loadHTML($view);
                if (method_exists($pdf, 'setPaper')) {
                    $pdf->setPaper('a5', 'portrait');
                }
                $file = 'tickets-' . str($event->title)->slug('-') . '.pdf';
                return $pdf->download($file);
            } catch (\Throwable $e) { /* fallback below */ }
        }

        return view('calendar.print', ['event' => $event, 'tickets' => $tickets, 'qrMap' => $qrMap]);
    }
}

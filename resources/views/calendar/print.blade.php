<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cetak Tiket — {{ $event->title }}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111827; }
    .page { width: 100%; max-width: 100%; margin: 0 auto; padding: 0; }
    .header { display: none; }
    .ticket { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12mm; min-height: calc(100vh - 20mm); display: flex; flex-direction: column; justify-content: center; }
    .qr { display:flex; justify-content:center; align-items:center; margin: 10mm 0; }
    .muted { color: #6b7280; font-size: 12px; }
    .sheet { page-break-after: always; break-inside: avoid; }
    .sheet:last-child { page-break-after: auto; }
    @media print { @page { size: A5 portrait; margin: 10mm; } }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <div>
        <div style="font-size:18px;font-weight:700;">{{ $event->title }}</div>
        <div class="muted">Dicetak: {{ now()->format('d M Y H:i') }}</div>
      </div>
    </div>

    @if(($tickets ?? collect())->isEmpty())
      <div class="ticket sheet">Tidak ada tiket.</div>
    @else
      @foreach($tickets as $t)
        <div class="sheet">
          <div class="ticket">
            <div style="font-weight:700; font-size:18px;">{{ $event->title }}</div>
            <div class="muted">Kode: {{ $t->code }}</div>
            <div class="qr">
              @php $src = ($qrMap[$t->code] ?? null) ?: route('ticket.qr', $t->code); @endphp
              <img src="{{ $src }}" alt="QR {{ $t->code }}" style="width:220px;height:220px;object-fit:contain;" />
            </div>
          </div>
        </div>
      @endforeach
    @endif
  </div>
</body>
</html>

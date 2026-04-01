<div class="space-y-3">
  @if($url)
    <img src="{{ $url }}" alt="Bukti transfer" class="max-h-96 w-auto rounded-lg shadow"> 
  @else
    <div class="text-sm text-gray-600">Bukti tidak tersedia.</div>
  @endif
  <div class="text-xs text-gray-500">Ref: {{ $record->reference }}</div>
</div>


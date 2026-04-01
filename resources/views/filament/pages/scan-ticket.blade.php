<x-filament::page>
    <div class="space-y-4">
        <form wire:submit="validateTicket">
            {{ $this->form }}
            <div class="mt-3">
                <x-filament::button type="submit" icon="heroicon-m-check-circle" icon-position="before">Validate</x-filament::button>
            </div>
        </form>

        @if($result)
            @php $st = $result['status'] ?? 'unknown'; $t = $result['ticket'] ?? null; @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                @if($st === 'validated')
                    <div class="text-green-700 font-semibold">Ticket validated and marked as used.</div>
                @elseif($st === 'issued')
                    <div class="text-amber-700 font-semibold">Issued (not used).</div>
                @elseif($st === 'used')
                    <div class="text-gray-700 font-semibold">Already used.</div>
                @elseif($st === 'void')
                    <div class="text-red-700 font-semibold">Ticket void.</div>
                @else
                    <div class="text-red-700 font-semibold">Ticket not found.</div>
                @endif

                @if($t)
                    <div class="mt-2 text-sm text-gray-700">
                        <div>Code: <span class="font-medium">{{ $t->code }}</span></div>
                        <div>Event: <span class="font-medium">{{ $t->event?->title }}</span></div>
                        <div>Status: <span class="font-medium uppercase">{{ $t->status }}</span></div>
                        <div>Used at: <span class="font-medium">{{ optional($t->used_at)->format('d M Y H:i') ?: '-' }}</span></div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament::page>

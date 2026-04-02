<div class="flex items-center gap-1">
    @php
        $record = $getRecord();
        $meta = $record->meta_json ?? [];
        $followups = $meta['followups'] ?? [];
        $buttons = [
            ['key' => 'w', 'label' => 'W', 'tooltip' => 'Welcome'],
            ['key' => 'fu1', 'label' => '1', 'tooltip' => 'Follow Up 1'],
            ['key' => 'fu2', 'label' => '2', 'tooltip' => 'Follow Up 2'],
            ['key' => 'fu3', 'label' => '3', 'tooltip' => 'Follow Up 3'],
        ];
    @endphp

    @foreach ($buttons as $btn)
        @php
            $sent = !empty($followups[$btn['key']]);
            $isWelcome = $btn['key'] === 'w';
            $bgSent = $isWelcome ? 'bg-green-500 text-white ring-green-300' : 'bg-amber-400 text-white ring-amber-200';
            $bgDefault = 'bg-gray-100 text-gray-400 ring-gray-200 hover:bg-gray-200';
        @endphp
        <button
            type="button"
            wire:click="mountTableAction('toggleFollowUp', '{{ $record->getKey() }}', { key: '{{ $btn['key'] }}' })"
            title="{{ $btn['tooltip'] }}{{ $sent ? ' ✓ Terkirim ' . $followups[$btn['key']] : '' }}"
            class="inline-flex items-center justify-center h-7 w-7 rounded-full text-xs font-bold ring-1 transition-all duration-200 {{ $sent ? $bgSent : $bgDefault }}"
        >
            {{ $btn['label'] }}
        </button>
    @endforeach
</div>

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
            $viawaba = !empty($followups[$btn['key'] . '_waba']);
            $isWelcome = $btn['key'] === 'w';
            // Green if welcome+sent, amber if followup+sent, brighter if via WABA
            if ($sent && $viawaba) {
                $bg = $isWelcome
                    ? 'bg-green-500 text-white ring-green-300 shadow-sm shadow-green-200'
                    : 'bg-amber-500 text-white ring-amber-300 shadow-sm shadow-amber-200';
            } elseif ($sent) {
                $bg = $isWelcome
                    ? 'bg-green-400 text-white ring-green-200'
                    : 'bg-amber-400 text-white ring-amber-200';
            } else {
                $bg = 'bg-gray-100 text-gray-400 ring-gray-200 hover:bg-gray-200';
            }
        @endphp
        <button
            type="button"
            wire:click="mountTableAction('toggleFollowUp', '{{ $record->getKey() }}', { key: '{{ $btn['key'] }}' })"
            title="{{ $btn['tooltip'] }}{{ $sent ? ' ✓ ' . $followups[$btn['key']] . ($viawaba ? ' (WABA)' : ' (Manual)') : '' }}"
            class="inline-flex items-center justify-center h-7 w-7 rounded-full text-xs font-bold ring-1 transition-all duration-200 {{ $bg }}"
        >
            {{ $btn['label'] }}
        </button>
    @endforeach
</div>

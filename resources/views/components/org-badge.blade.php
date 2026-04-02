@props(['org', 'size' => 'sm', 'color' => 'gray'])

@php
    $sizes = [
        'xs' => ['img' => 'h-3.5 w-3.5', 'text' => 'text-[11px]', 'badge' => 'h-3 w-3'],
        'sm' => ['img' => 'h-4 w-4', 'text' => 'text-xs', 'badge' => 'h-3.5 w-3.5'],
        'md' => ['img' => 'h-5 w-5', 'text' => 'text-sm', 'badge' => 'h-4 w-4'],
    ];
    $s = $sizes[$size] ?? $sizes['sm'];
    $colors = [
        'gray' => 'text-gray-400 hover:text-gray-600',
        'white' => 'text-white/80 hover:text-white',
    ];
    $c = $colors[$color] ?? $colors['gray'];
@endphp

<a href="{{ $org->slug ? route('organization.show', $org->slug) : '#' }}"
   class="inline-flex items-center gap-1.5 {{ $c }} transition-colors z-20 relative">
    @if ($org->logo_url)
        <img src="{{ $org->logo_url }}" alt="{{ $org->name }}"
             class="{{ $s['img'] }} rounded-full object-cover ring-1 ring-black/10" />
    @endif
    <span class="{{ $s['text'] }}">by {{ $org->name }}</span>
    @if ($org->is_verified)
        <svg class="{{ $s['badge'] }} text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
        </svg>
    @endif
</a>

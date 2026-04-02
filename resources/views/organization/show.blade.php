@extends('layouts.storefront')

@section('title', $org->name)

@section('content')
<main class="mx-auto max-w-2xl pb-24">
    {{-- Org Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
        <div class="flex items-center gap-4">
            @if ($org->logo_url)
                <img src="{{ $org->logo_url }}" alt="{{ $org->name }}"
                     class="h-16 w-16 rounded-xl object-cover ring-1 ring-gray-200 shadow-sm" />
            @else
                <div class="h-16 w-16 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center">
                    <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-gray-900">{{ $org->name }}</h1>
                    @if ($org->is_verified)
                        <svg class="h-5 w-5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                @if ($org->summary)
                    <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $org->summary }}</p>
                @endif
            </div>
        </div>

        {{-- Social Links --}}
        @if (!empty($org->social_json))
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach (['website' => '🌐', 'instagram' => '📸', 'facebook' => '📘', 'youtube' => '▶️', 'tiktok' => '🎵'] as $key => $icon)
                    @if (!empty($org->social_json[$key]))
                        <a href="{{ str_starts_with($org->social_json[$key], 'http') ? $org->social_json[$key] : 'https://' . $org->social_json[$key] }}"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 bg-gray-50 px-2.5 py-1.5 rounded-lg transition-colors">
                            <span>{{ $icon }}</span>
                            <span>{{ ucfirst($key) }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Events Section --}}
    @if ($events->count() > 0)
        <section class="mb-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Event ({{ $events->count() }})</h2>
            </div>
            <div class="grid grid-cols-1 gap-3">
                @foreach ($events as $e)
                    <x-event-card :event="$e" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Campaigns Section --}}
    @if ($campaigns->count() > 0)
        <section class="mb-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Campaign Donasi ({{ $campaigns->count() }})</h2>
            </div>
            <div class="space-y-3">
                @foreach ($campaigns as $c)
                    @php
                        $target = (float) $c->target_amount;
                        $raised = (float) $c->raised_amount;
                        $pct = $target > 0 ? min(100, round(($raised / $target) * 100)) : 0;
                        $daysLeft = $c->end_date ? max(0, (int) now()->diffInDays($c->end_date, false)) : null;
                    @endphp
                    <a href="{{ route('campaign.show', $c->slug) }}"
                       class="group flex items-start gap-3 overflow-hidden rounded-xl border border-gray-200 bg-white p-3 shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-200">
                        <div class="relative h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg">
                            @if ($c->cover_url)
                                <img src="{{ $c->cover_url }}" alt="{{ $c->title }}"
                                     class="h-full w-full object-cover" loading="lazy" />
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-amber-100 to-orange-100">
                                    <svg class="h-8 w-8 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 group-hover:text-amber-600 line-clamp-1 transition-colors">
                                {{ $c->title }}
                            </h3>
                            <div class="mt-2">
                                <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500 transition-all duration-500"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            <div class="mt-1.5 flex items-center gap-3 text-xs">
                                <span class="font-bold text-amber-600">Rp {{ number_format($raised, 0, ',', '.') }}</span>
                                <span class="text-gray-300">•</span>
                                <span class="text-gray-400">Target Rp {{ number_format($target, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Empty State --}}
    @if ($events->count() === 0 && $campaigns->count() === 0)
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            <p class="mt-3 text-sm text-gray-500">Belum ada event atau campaign dari organisasi ini.</p>
        </div>
    @endif
</main>
@endsection

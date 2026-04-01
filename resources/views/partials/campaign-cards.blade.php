@foreach ($campaigns as $c)
    @php
        $cover = optional($c->media->sortBy('sort_order')->first())->url;
        $progress = (float) $c->target_amount > 0 ? min(100, round(((float) $c->raised_amount / (float) $c->target_amount) * 100)) : 0;
    @endphp
    <article class="relative overflow-hidden rounded-md border border-gray-200 bg-white shadow hover:shadow-md hover:border-sky-300 cursor-pointer">
        <a href="{{ route('campaign.show', $c->slug) }}" aria-label="Lihat detail {{ $c->title }}" class="absolute inset-0 z-10"></a>
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $c->title }}" class="w-full object-cover " />
        @else
            <div class="flex h-44 w-full items-center justify-center bg-gray-100 text-gray-400">Tidak ada gambar</div>
        @endif
        <div class="space-y-3 p-4">
            <h2 class="line-clamp-2 text-lg font-semibold">{{ $c->title }}</h2>
            @if ($c->summary)
                <p class="line-clamp-3 text-sm text-gray-600">{{ $c->summary }}</p>
            @endif
            <div class="space-y-1">
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                    <div class="h-full bg-sky-500" style="width: {{ $progress }}%"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-600">
                    <span>Terkumpul: Rp {{ number_format((float) $c->raised_amount, 2, ',', '.') }}</span>
                    <span>Target: Rp {{ number_format((float) $c->target_amount, 2, ',', '.') }}</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-2">
                @foreach ($c->categories as $cat)
                    <a href="{{ route('home', ['category' => $cat->slug]) }}" class="relative z-20 rounded-full bg-sky-50 px-2 py-1 text-xs text-sky-700 ring-1 ring-sky-200 hover:bg-sky-100">#{{ $cat->name }}</a>
                @endforeach
            </div>
            <a href="{{ route('campaign.show', $c->slug) }}"
               class="relative z-20 mt-3 inline-flex w-full items-center justify-center rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-orange-600">
                Donasi Sekarang
            </a>
        </div>
    </article>
@endforeach

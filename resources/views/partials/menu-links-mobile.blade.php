@php
    use App\Models\Menu;
    $code = config('storefront.menu_code', 'main');
    $menu = Menu::query()->where('code', $code)->first();
    $items = $menu
        ? $menu->items()
            ->whereNull('parent_id')
            ->where('active', true)
            ->with(['page', 'news', 'campaign', 'children' => function($q) {
                $q->where('active', true)->orderBy('sort_order');
            }, 'children.page', 'children.news', 'children.campaign'])
            ->orderBy('sort_order')
            ->get()
        : collect();

    $makeUrl = function($it) {
        if (!empty($it->url)) return $it->url;
        if ($it->page) return route('page.show', $it->page->slug);
        if ($it->news) return route('news.show', $it->news->slug);
        if ($it->campaign) return route('campaign.show', $it->campaign->slug);
        return '#';
    };
@endphp

@if ($items->isNotEmpty())
    @foreach ($items as $it)
        @php $url = $makeUrl($it); $isActive = rtrim(url()->current(), '/') === rtrim($url, '/'); @endphp
        @if ($it->children->isNotEmpty())
            <details class="group">
                <summary class="flex cursor-pointer items-center justify-between rounded-md px-3 py-2 text-sm {{ $isActive ? 'bg-sky-50 text-sky-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span>{{ $it->title }}</span>
                    <span class="ml-2 text-gray-500 group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <div class="mt-1 ml-3 border-l border-gray-200 pl-2">
                    @foreach ($it->children as $child)
                        @php $cu = $makeUrl($child); $cActive = rtrim(url()->current(), '/') === rtrim($cu, '/'); @endphp
                        <a href="{{ $cu }}" class="block rounded-md px-3 py-2 text-sm {{ $cActive ? 'bg-sky-50 text-sky-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ $child->title }}</a>
                    @endforeach
                </div>
            </details>
        @else
            <a href="{{ $url }}"
               class="block rounded-md px-3 py-2 text-sm {{ $isActive ? 'bg-sky-50 text-sky-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ $it->title }}</a>
        @endif
    @endforeach
@else
    <a href="{{ route('program.index') }}"
       class="block rounded-md px-3 py-2 text-sm {{ (request()->routeIs('program.*') || request()->routeIs('home')) ? 'bg-sky-50 text-sky-700' : 'text-gray-700 hover:bg-gray-50' }}">Program</a>
    <a href="{{ route('news.index') }}"
       class="mt-1 block rounded-md px-3 py-2 text-sm {{ request()->routeIs('news.*') ? 'bg-sky-50 text-sky-700' : 'text-gray-700 hover:bg-gray-50' }}">Berita</a>
@endif

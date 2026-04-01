@php
    use App\Models\Menu;

    // Read menu
    $code = config('storefront.menu_code', 'main');
    $menu = Menu::query()->where('code', $code)->first();

    /** @var Illuminate\Support\Collection $items */
    $items = $menu
        ? $menu->items()
            ->whereNull('parent_id')
            ->where('active', true)
            ->with([
                'page', 'news', 'campaign',
                'children' => function ($q) {
                    $q->where('active', true)->orderBy('sort_order');
                },
                'children.page', 'children.news', 'children.campaign'
            ])
            ->orderBy('sort_order')
            ->get()
        : collect();

    // Helpers
    $makeUrl = function ($it) {
        if (!empty($it->url)) return $it->url;
        if ($it->page) return route('page.show', $it->page->slug);
        if ($it->news) return route('news.show', $it->news->slug);
        if ($it->campaign) return route('campaign.show', $it->campaign->slug);
        return '#';
    };

    /**
     * Render a single menu item (supports submenu)
     * - Desktop: hover to show
     * - Mobile: tap/click to toggle (Alpine.js)
     */
    $renderItem = function ($it, $tabBase, $tabActive) use (&$renderItem, $makeUrl) {
        $url = $makeUrl($it);
        $current = rtrim(url()->current(), '/');
        $isActive = $current === rtrim($url, '/');
        $hasChildren = $it->relationLoaded('children') && $it->children->isNotEmpty();

        if (! $hasChildren) {
            echo '<a href="' . e($url) . '" class="' . e($isActive ? $tabActive : $tabBase) . '">' . e($it->title) . '</a>';
            return;
        }

        // Determine if any child is active to highlight parent
        $childActive = false;
        foreach ($it->children as $c) {
            $cu = $makeUrl($c);
            if ($current === rtrim($cu, '/')) { $childActive = true; break; }
        }
        $parentClass = $childActive || $isActive ? $tabActive : $tabBase;

        // Use <details>/<summary> for built-in toggle without JS
        echo '<details class="relative inline-block group">';
        echo '<summary class="' . e($parentClass) . ' flex items-center cursor-pointer list-none">' . e($it->title) . ' <span class="ml-1 inline-block align-middle select-none">▾</span></summary>';

        // Dropdown content auto-toggles via <details>
        echo '<div class="absolute left-0 z-30 mt-2 w-max rounded-md border border-gray-200 bg-white py-2 shadow-lg">';
        foreach ($it->children as $child) {
            $cu = $makeUrl($child);
            $cActive = $current === rtrim($cu, '/');
            echo '<a href="' . e($cu) . '" class="block px-4 py-2 text-sm ' . e($cActive ? 'bg-sky-50 text-sky-700' : 'text-gray-700 hover:bg-gray-50') . '">' . e($child->title) . '</a>';
        }
        echo '</div>';

        echo '</details>';
    };
@endphp

{{--
  USAGE NOTES
  - Pastikan wrapper <nav> TIDAK memakai overflow-hidden. Pakai: <nav class="relative overflow-visible">
  - Tambahkan Alpine.js (di layout): <script defer src="https://unpkg.com/alpinejs"></script>
  - $tabBase & $tabActive adalah kelas Tailwind untuk tab normal & aktif yang sudah Anda definisikan di luar.
--}}

@if ($items->isNotEmpty())
    @foreach ($items as $it)
        {!! $renderItem($it, $tabBase ?? '', $tabActive ?? '') !!}
    @endforeach
@else
    {{-- Fallback to default links when no menu configured --}}
    @php
        $def = [
            ['title' => 'Program', 'url' => route('program.index'), 'active' => (request()->routeIs('program.*') || request()->routeIs('home'))],
            ['title' => 'Berita', 'url' => route('news.index'), 'active' => request()->routeIs('news.*')],
        ];
    @endphp
    @foreach ($def as $it)
        <a href="{{ $it['url'] }}" class="{{ $it['active'] ? ($tabActive ?? '') : ($tabBase ?? '') }}">{{ $it['title'] }}</a>
    @endforeach
@endif

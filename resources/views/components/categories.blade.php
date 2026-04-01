@props([
  'items' => [], // array: [{ title, img, slug }]
  'active' => null,
  'q' => null,
  'title' => 'Kategori',
  'seeAllHref' => null,
  'routeName' => 'home', // allow override to stay on specific page
])

<section class="relative">
  <div class="flex items-center justify-between gap-4 mb-3">
    <h2 class="text-sm font-semi">{{ $title }}</h2>

    {{-- <div x-data class="hidden sm:flex items-center gap-2">
      <button x-on:click="$refs.track.scrollBy({left:-320, behavior:'smooth'})"
              class="rounded-xl ring-1 ring-gray-300 px-3 py-2 text-sm hover:bg-gray-50">←</button>
      <button x-on:click="$refs.track.scrollBy({left: 320, behavior:'smooth'})"
              class="rounded-xl ring-1 ring-gray-300 px-3 py-2 text-sm hover:bg-gray-50">→</button>
    </div> --}}
  </div>

  <div class="relative">
    <div x-data x-ref="track"
         class="w-full overflow-x-auto overflow-y-hidden no-scrollbar scroll-smooth snap-x snap-mandatory scroll-px-4">
      @php
        $list = $items;
        if (empty($list)) {
            $list = [
                ['title' => 'Music Festivals', 'img' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=60&fit=crop&w=640&h=360'],
                ['title' => 'Sport Events',    'img' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?q=60&fit=crop&w=640&h=360'],
                ['title' => 'Fashion Shows',   'img' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?q=60&fit=crop&w=640&h=360'],
                ['title' => 'Book Fair',       'img' => 'https://images.unsplash.com/photo-1519681393784-d120267933ba?q=60&fit=crop&w=640&h=360'],
            ];
        }
      @endphp
      <ul class="flex gap-2 min-w-max pr-4">
        @php
          $grads = [
            'from-violet-400 to-fuchsia-400',
            'from-amber-400 to-orange-400',
            'from-cyan-400 to-sky-400',
            'from-rose-400 to-orange-400',
          ];
        @endphp
        @foreach ($list as $i => $item)
          @php
            $isActive = $active === ($item['slug'] ?? null);
            $grad = $grads[$i % count($grads)];
          @endphp
          <li class="snap-start shrink-0 w-20">
            <a href="{{ route($routeName, ['category' => $item['slug'] ?? null, 'q' => $q]) }}"
               aria-current="{{ $isActive ? 'page' : 'false' }}"
               class="relative block w-20 h-20 rounded-xl overflow-hidden shadow-sm ring-2 {{ $isActive ? 'ring-sky-500' : 'ring-black/10' }} bg-gradient-to-br {{ $grad }}">
              <div class="absolute -top-3 -left-2 w-16 h-16 rounded-xl rotate-[-12deg] overflow-hidden ring-2 ring-white/60 shadow-md">
                <img src="{{ $item['img'] ?? '' }}" alt="{{ $item['title'] ?? '' }}" loading="lazy" class="w-full h-full object-cover" />
              </div>
            </a>
            <div class="mt-1 w-20 text-center">
              <span class="block truncate text-[10px] font-semibold {{ $isActive ? 'text-sky-700' : 'text-gray-700' }}">
                {{ $item['title'] ?? '' }}
              </span>
            </div>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</section>

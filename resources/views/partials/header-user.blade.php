@php
    $notif = (int) ($notif ?? 0);
    $user = $user ?? auth()->user();
    $location = $location ?? ($org->name ?? 'Location');
    $query = isset($query) ? (string) $query : (string) ($q ?? request('q', ''));
    $notifUrl = \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : url('/notifications');
    // Cart summary (session based)
    $cartItems = (array) session('cart.items', []);
    $cartCount = 0;
    foreach ($cartItems as $it) {
        $cartCount += (int) ($it['qty'] ?? 1);
    }
    $cartUrl = \Illuminate\Support\Facades\Route::has('cart.index') ? route('cart.index') : url('/cart');
    // Optional overrides
    $searchRouteName = $searchRouteName ?? 'home';
    $showFilterButton = (bool) ($showFilterButton ?? false);
@endphp

<div class="space-y-3">

    @if($user)
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start gap-3">
                <img src="{{ $user?->profile_photo_url ?? ($user?->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($user?->name ?? 'U') . '&background=E5E7EB&color=111827') }}"
                     alt="Avatar" class="h-10 w-10 rounded-full object-cover ring-1 ring-gray-200" />

                <div>
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M12 21s-6.5-5.1-6.5-10.2A6.5 6.5 0 1 1 18.5 10.8C18.5 15.9 12 21 12 21z" />
                            <circle cx="12" cy="10.8" r="2.2" stroke-width="1.8" />
                        </svg>
                        <span>Indonesia</span>

                        {{-- <button type="button" class="ml-1 inline-flex items-center justify-center rounded-md"
                                aria-label="Change location" title="Change location">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                 fill="currentColor">
                                <path d="M7 10l5 5 5-5H7z" />
                            </svg>
                        </button> --}}
                    </div>

                    <div class="text-md font-semi leading-6 text-gray-500">
                        {{ $user?->name }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $notifUrl }}"
                   class="relative inline-flex h-9 w-9 items-center justify-center rounded-full ring-1 ring-gray-200 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" class="h-5 w-5 text-gray-800" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>

                    @if($notif > 0)
                        <span
                              class="absolute -right-0.5 -top-0.5 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold text-white ring-2 ring-white">
                            {{ $notif > 9 ? '9+' : $notif }}
                        </span>
                    @endif
                </a>

                <a href="{{ $cartUrl }}"
                   class="relative inline-flex h-9 w-9 items-center justify-center rounded-full ring-1 ring-gray-200 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" class="h-5 w-5 text-gray-800" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>

                    @if($cartCount > 0)
                        <span
                              class="absolute -right-0.5 -top-0.5 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold text-white ring-2 ring-white">
                            {{ $cartCount > 9 ? '9+' : $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    @endif

    <div class="relative" data-search-box>
        <form method="get" action="{{ route($searchRouteName) }}">
            <input type="text" placeholder="Search Event"
                   class="w-full rounded-xl pl-10 pr-10 py-3 text-sm text-gray-800 placeholder-gray-400 ring-1 ring-gray-200 focus:outline-none focus:ring-2 focus:ring-sky-500"
                   name="q" value="{{ $query }}" aria-label="Search events" />


            <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor">
                    <circle cx="11" cy="11" r="7" stroke-width="1.8" />
                    <path d="M20 20l-3-3" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </div>

            <div class="absolute inset-y-0 right-2 my-auto flex items-center gap-1">
                <span data-clear-container class="{{ ($query ?? '') === '' ? 'hidden' : '' }}">
                    <button type="button" data-clear-search
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full hover:bg-gray-200"
                            aria-label="Clear search" title="Clear">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </span>
                @if($showFilterButton)
                    <button type="button" id="event-filter-open"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full hover:bg-gray-200"
                            aria-label="Filter tanggal" title="Filter">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M3 6h18M6 12h12M10 18h4" />
                        </svg>
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('[data-search-box]').forEach((box) => {
            const form = box.querySelector('form');
            const input = form ? form.querySelector('input[name="q"]') : null;
            const clearWrap = form ? form.querySelector('[data-clear-container]') : null;
            const clearBtn = form ? form.querySelector('[data-clear-search]') : null;
            if (!form || !input || !clearWrap || !clearBtn) return;
            const sync = () => { if (input.value && input.value.trim().length > 0) { clearWrap.classList.remove('hidden'); } else { clearWrap.classList.add('hidden'); } };
            input.addEventListener('input', sync);
            clearBtn.addEventListener('click', (e) => { e.preventDefault(); input.value = ''; sync(); form.submit(); });
            // Initialize state on load
            sync();
        });
    })();
</script>

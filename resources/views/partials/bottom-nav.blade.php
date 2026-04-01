@php
    $homeActive = request()->routeIs('home');
    $eventsActive = request()->routeIs('event.index') || (request()->routeIs('program.index') && request('view') !== 'calendar');
    $calendarActive = request()->routeIs('calendar.index') || (request()->routeIs('program.index') && request('view') === 'calendar');
    $profileActive = request()->is('profile');
    $cartActive = request()->routeIs('cart.index');
    // Session cart summary
    $cartItems = (array) session('cart.items', []);
    $cartCount = 0; foreach ($cartItems as $it) { $cartCount += (int) ($it['qty'] ?? 1); }
    $hasCart = $cartCount > 0;
    $gridCols = $hasCart ? 'grid-cols-5' : 'grid-cols-4';
@endphp

<nav class="bottom-nav fixed inset-x-0 bottom-0 z-40" style="margin-bottom: -11px;">
    <div class="mx-auto w-full">
        <div
             class="grid {{ $hasCart ? 'grid-cols-4' : 'grid-cols-4' }} items-stretch justify-center rounded-t-2xl bg-white/95 backdrop-blur border-t border-gray-200 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
            <a href="{{ route('home') }}"
               class="flex flex-col items-center justify-center py-2 text-xs {{ $homeActive ? 'text-sky-600' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>

                <span class="mt-1">Home</span>
            </a>

            <a href="{{ route('event.index') }}"
               class="flex flex-col items-center justify-center py-2 text-xs {{ $eventsActive ? 'text-sky-600' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                </svg>
                <span class="mt-1">Event</span>
            </a>

            <a href="{{ route('calendar.index') }}"
               class="flex flex-col items-center justify-center py-2 text-xs {{ $calendarActive ? 'text-sky-600' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" class="h-6 w-6" viewBox="0 0 24 24"
                     stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>

                <span class="mt-1">Calendar</span>
            </a>

            {{-- @if($hasCart)
                <a href="{{ route('cart.index') }}"
                   class="flex flex-col items-center justify-center py-2 text-xs {{ $cartActive ? 'text-sky-600' : 'text-gray-500' }}">
                    <span class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" class="h-6 w-6 text-gray-800" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span class="absolute -right-2 -top-1 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold text-white ring-2 ring-white">
                            {{ $cartCount > 9 ? '9+' : $cartCount }}
                        </span>
                    </span>
                    <span class="mt-1">Cart</span>
                </a>
            @endif --}}

            @php $profileUrl = auth()->check() ? route('profile.index') : route('login'); @endphp
            <a href="{{ $profileUrl }}"
               class="flex flex-col items-center justify-center py-2 text-xs {{ $profileActive ? 'text-sky-600' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M15 8a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20a8 8 0 1116 0" />
                </svg>
                <span class="mt-1">Profile</span>
            </a>
        </div>
    </div>
</nav>

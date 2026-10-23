{{-- The bar across the top of a screen: where you are, where else to go, and who you are. --}}
{{--
  @var string $companyName
  @var string $name
  @var \App\Models\Staff|null $staff
  @var string|null $teamUrl
--}}
@props([
  'companyName',
  'name',
  'staff' => null,
  'teamUrl' => null,
])

@php
  /*
   * The four parts of the salon the menu offers. Only the ones that have a
   * screen carry a URL; the rest are drawn so that the shape of the menu is the
   * shape of the product, and are not links until there is something to link to.
   */
  $sections = [
    [
      'label' => __('Dashboard'),
      'shortcut' => '^1',
      'hue' => 250,
      'url' => route('dashboard.index'),
      'icon' => '<rect x="3.5" y="3.5" width="7" height="8" rx="1.5"></rect><rect x="13.5" y="3.5" width="7" height="5" rx="1.5"></rect><rect x="13.5" y="11.5" width="7" height="9" rx="1.5"></rect><rect x="3.5" y="14.5" width="7" height="6" rx="1.5"></rect>',
    ],
    [
      'label' => __('Clients'),
      'shortcut' => '^2',
      'hue' => 30,
      'url' => null,
      'icon' => '<rect x="3.5" y="5" width="17" height="14" rx="2"></rect><circle cx="9" cy="11" r="2.2"></circle><path d="M5.8 16.2c.6-1.6 1.8-2.4 3.2-2.4s2.6.8 3.2 2.4"></path><path d="M14.5 10h3.5"></path><path d="M14.5 13.5h3.5"></path>',
    ],
    [
      'label' => __('Services'),
      'shortcut' => '^3',
      'hue' => 80,
      'url' => null,
      'icon' => '<circle cx="6.5" cy="7" r="2.5"></circle><circle cx="6.5" cy="17" r="2.5"></circle><path d="M8.6 8.4 20 17"></path><path d="M8.6 15.6 20 7"></path>',
    ],
    [
      'label' => __('Team'),
      'shortcut' => '^4',
      'hue' => 310,
      'url' => $teamUrl,
      'icon' => '<circle cx="9" cy="8.5" r="3"></circle><path d="M3.5 19c.8-3 2.9-4.5 5.5-4.5s4.7 1.5 5.5 4.5"></path><circle cx="16.5" cy="9.5" r="2.4"></circle><path d="M16 14.6c2.1 0 3.8 1.3 4.5 3.9"></path>',
    ],
  ];

  $yours = [
    ['label' => __('Profile'), 'url' => route('settings.profile.index')],
    ['label' => __('Logs'), 'url' => route('settings.logs.index')],
    ['label' => __('Security and access'), 'url' => route('settings.security.index')],
    ['label' => __('Preferences'), 'url' => route('settings.preferences.index')],
  ];
@endphp

<header class="flex flex-wrap items-center gap-5 pt-4.5 pb-4">
  <x-logo />

  <!-- where else you can go -->
  <nav class="relative flex min-w-55 flex-1 items-center justify-center">
    <button
      type="button"
      @click="menuOpen = ! menuOpen"
      :aria-expanded="menuOpen ? 'true' : 'false'"
      aria-controls="top-bar-menu"
      class="flex max-w-full cursor-pointer items-center gap-2.5 rounded-[14px] bg-accent-soft px-5 py-2.75"
    >
      <span class="truncate text-[17px] font-bold tracking-tight text-ink">{{ $companyName }}</span>

      <span
        class="block size-2 shrink-0 -translate-y-0.75 rotate-45 border-r-2 border-b-2 border-accent-ink transition-transform"
        :class="menuOpen && 'translate-y-0.75! -rotate-[135deg]!'"
        aria-hidden="true"
      ></span>
    </button>

    <div
      x-cloak
      x-show="menuOpen"
      @click="menuOpen = false"
      class="fixed inset-0 z-30"
      aria-hidden="true"
    ></div>

    <div
      x-cloak
      x-show="menuOpen"
      x-transition.opacity.duration.150ms
      x-trap="menuOpen"
      id="top-bar-menu"
      class="absolute top-13 left-1/2 z-40 w-140 max-w-[92vw] -translate-x-1/2 rounded-[20px] bg-menu p-4.5 shadow-2xl"
    >
      <p class="mb-3.5 text-center text-sm text-white">
        {{ __('Press') }}
        <span class="rounded-[5px] bg-white/20 px-1.5 py-px font-bold">G</span>
        {{ __('to load this menu from anywhere') }}
      </p>

      <input
        type="text"
        aria-label="{{ __('Search') }}"
        placeholder="{{ __('Type to go to a client, a service, or a person…') }}"
        class="mb-3.5 block w-full rounded-xl bg-white px-3.75 py-3 text-[15px] text-[#17161a] ring-3 ring-white/25 outline-none placeholder:text-placeholder"
      />

      <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach ($sections as $section)
          @if ($section['url'])
            <a href="{{ $section['url'] }}" data-turbo="true" class="relative grid content-center justify-items-center gap-3 rounded-[14px] bg-white/8 px-2 pt-6 pb-4.5 focus-visible:outline-2 focus-visible:outline-white transition-colors hover:bg-white/20">
          @else
            <button type="button" aria-disabled="true" class="relative grid cursor-default content-center justify-items-center gap-3 rounded-[14px] bg-white/8 px-2 pt-6 pb-4.5 focus-visible:outline-2 focus-visible:outline-white">
          @endif
              <span class="absolute top-2 right-2.5 text-xs font-semibold text-white/60" aria-hidden="true">{{ $section['shortcut'] }}</span>
              <span
                class="grid size-9.5 place-items-center rounded-xl"
                style="background: oklch(0.6 0.13 {{ $section['hue'] }} / 0.24); box-shadow: inset 0 0 0 1.5px oklch(0.7 0.12 {{ $section['hue'] }} / 0.32); color: oklch(0.85 0.11 {{ $section['hue'] }})"
                aria-hidden="true"
              >
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $section['icon'] !!}</svg>
              </span>
              <span class="text-[15px] font-semibold text-white">{{ $section['label'] }}</span>
          @if ($section['url'])
            </a>
          @else
            </button>
          @endif
        @endforeach
      </div>

      <p class="mt-5 mb-1.5 pl-3 text-[13px] font-bold tracking-[0.08em] text-white">{{ __('YOURS') }}</p>

      <div class="grid grid-cols-2 gap-0.5">
        @foreach ($yours as $link)
          <a
            href="{{ $link['url'] }}"
            data-turbo="true"
            class="flex items-center gap-3 rounded-[10px] px-3 py-2.25 transition-colors hover:bg-white/16 focus-visible:outline-2 focus-visible:outline-white"
          >
            <span class="block size-1.75 shrink-0 rounded-full bg-white/45" aria-hidden="true"></span>
            <span class="text-[15px] font-semibold text-white">{{ $link['label'] }}</span>
          </a>
        @endforeach
      </div>
    </div>
  </nav>

  <!-- who you are -->
  <div id="top-bar-identity" class="flex items-center gap-2.25">
    <span class="text-sm text-body">{{ $name }}</span>

    <x-avatar :staff="$staff" :name="$name" :size="28" />
  </div>
</header>

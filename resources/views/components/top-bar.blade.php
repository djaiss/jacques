{{-- The bar across the top of a screen: where you are, where else to go, and who you are. --}}
{{--
  @var string $companyName
  @var string $name
  @var \App\Models\Employee|null $employee
--}}
@props([
  'companyName',
  'name',
  'employee' => null,
])

@php
  /*
   * The six parts of the application the menu offers. Only the ones that have a
   * screen carry a URL; the rest are drawn so that the shape of the menu is the
   * shape of the product, and are not links until there is something to link to.
   */
  $sections = [
    ['label' => __('People'), 'shortcut' => '^1', 'url' => null, 'glyph' => 'width:20px;height:20px;border-radius:999px;background:oklch(0.78 0.14 250)'],
    ['label' => __('Assets'), 'shortcut' => '^2', 'url' => null, 'glyph' => 'width:20px;height:20px;border-radius:6px;background:oklch(0.78 0.14 30)'],
    ['label' => __('Software'), 'shortcut' => '^3', 'url' => null, 'glyph' => 'width:17px;height:17px;border-radius:3px;transform:rotate(45deg);background:oklch(0.8 0.15 150)'],
    ['label' => __('Licences'), 'shortcut' => '^4', 'url' => null, 'glyph' => 'width:20px;height:20px;border-radius:999px;box-shadow:inset 0 0 0 4px oklch(0.8 0.14 310)'],
    ['label' => __('Reports'), 'shortcut' => '^5', 'url' => null, 'glyph' => 'width:20px;height:20px;border-radius:999px;background:conic-gradient(oklch(0.85 0.15 85) 0 62%, oklch(0.85 0.15 85 / 0.3) 62% 100%)'],
    ['label' => __('Locations'), 'shortcut' => '^6', 'url' => route('settings.locations.index'), 'glyph' => 'width:18px;height:18px;clip-path:polygon(50% 0,100% 100%,0 100%);background:oklch(0.8 0.13 20)'],
  ];

  $yours = [
    ['label' => __('Profile'), 'url' => route('settings.profile.index')],
    ['label' => __('Logs'), 'url' => route('settings.logs.index')],
    ['label' => __('Security and access'), 'url' => route('settings.security.index')],
    ['label' => __('Preferences'), 'url' => route('settings.preferences.index')],
  ];

  $tile = 'relative grid content-center justify-items-center gap-2.5 rounded-xl px-2 pt-5 pb-4 text-center';
@endphp

<header class="flex flex-wrap items-center gap-5 pt-4.5 pb-4">
  <x-logo />

  <!-- where else you can go -->
  <nav class="relative flex min-w-55 flex-1 items-center justify-center">
    <button
      type="button"
      @click="menuOpen = ! menuOpen"
      :class="menuOpen && 'bg-accent-soft'"
      :aria-expanded="menuOpen ? 'true' : 'false'"
      aria-controls="top-bar-menu"
      class="flex cursor-pointer items-center gap-2.5 rounded-[14px] px-5 py-2.75 transition-colors hover:bg-accent-soft"
    >
      <span class="text-[17px] font-bold tracking-tight text-ink">{{ $companyName }}</span>

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
      class="absolute top-11.5 left-1/2 z-40 w-130 max-w-[88vw] -translate-x-1/2 rounded-[20px] bg-menu p-4.5 shadow-2xl"
    >
      <p class="mb-3 text-center text-sm text-white">
        {{ __('Press') }}
        <span class="rounded-[5px] bg-white/20 px-1.5 py-px font-bold">G</span>
        {{ __('to load this menu from anywhere') }}
      </p>

      <input
        type="text"
        aria-label="{{ __('Search') }}"
        placeholder="{{ __('Type to go to a person, an asset, or a label…') }}"
        class="mb-3.5 w-full rounded-xl bg-white px-3.75 py-3 text-[15px] text-ink outline-none placeholder:text-placeholder focus:ring-2 focus:ring-focus-ring"
      />

      <div class="grid grid-cols-3 gap-2">
        @foreach ($sections as $section)
          @if ($section['url'])
            <a href="{{ $section['url'] }}" data-turbo="true" class="{{ $tile }} bg-white/8 transition-colors hover:bg-white/28">
              <span class="absolute top-2 right-2.5 text-xs font-semibold text-white">{{ $section['shortcut'] }}</span>
              <span class="block shrink-0" style="{{ $section['glyph'] }}" aria-hidden="true"></span>
              <span class="text-[15px] font-semibold text-white">{{ $section['label'] }}</span>
            </a>
          @else
            <span class="{{ $tile }} bg-white/4 opacity-45">
              <span class="absolute top-2 right-2.5 text-xs font-semibold text-white">{{ $section['shortcut'] }}</span>
              <span class="block shrink-0" style="{{ $section['glyph'] }}" aria-hidden="true"></span>
              <span class="text-[15px] font-semibold text-white">{{ $section['label'] }}</span>
            </span>
          @endif
        @endforeach
      </div>

      <p class="mt-4.5 mb-2 text-[13px] font-bold tracking-[0.08em] text-white">{{ __('YOURS') }}</p>

      <div class="grid gap-0.5">
        @foreach ($yours as $link)
          <a
            href="{{ $link['url'] }}"
            data-turbo="true"
            class="flex items-center gap-3 rounded-[10px] px-3 py-2.25 transition-colors hover:bg-white/16"
          >
            <span class="block size-2 shrink-0 rounded-full bg-white/50" aria-hidden="true"></span>
            <span class="text-[15px] font-semibold text-white">{{ $link['label'] }}</span>
          </a>
        @endforeach
      </div>
    </div>
  </nav>

  <!-- who you are -->
  <div id="top-bar-identity" class="flex items-center gap-2.25">
    <span class="text-sm text-body">{{ $name }}</span>

    <x-avatar :employee="$employee" :name="$name" :size="28" />
  </div>
</header>

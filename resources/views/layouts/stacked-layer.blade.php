{{-- The shell of a screen that needs the whole window. --}}
{{--
  @var string $backTitle
  @var string $backUrl
  @var string|null $title
  @var \Illuminate\View\ComponentSlot|null $header
  @var \Illuminate\View\ComponentSlot|null $actions
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-layer-backdrop font-sans text-body antialiased">
    <a href="#main" class="sr-only z-80 rounded-[10px] bg-primary px-4 py-2.5 text-[15px] font-semibold text-on-primary focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:ring-2 focus:ring-focus-ring focus:outline-none">{{ __('Skip to content') }}</a>

    <div
      {{ $attributes->merge(['x-data' => '{}', 'class' => 'flex min-h-screen flex-col']) }}
      @keydown.escape.window="if (! document.querySelector('[data-escape-guard]')) window.location.assign(@js($backUrl))"
    >
      <div class="mx-19 h-2.75 rounded-t-xl border border-b-0 border-hairline-strong bg-layer-stack" aria-hidden="true"></div>

      <a
        href="{{ $backUrl }}"
        data-turbo="true"
        class="mx-11.5 flex items-center gap-2.5 rounded-t-xl border border-b-0 border-hairline-strong bg-layer-bar px-4.5 py-2 transition-colors hover:bg-layer-stack"
      >
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="shrink-0 text-muted" aria-hidden="true">
          <line x1="13" y1="8" x2="3" y2="8"></line>
          <path d="M6.6 4.4 3 8l3.6 3.6"></path>
        </svg>

        <span class="truncate text-xs text-body">{{ $backTitle }}</span>

        <span class="ml-auto hidden items-center gap-1.75 text-xs text-muted-soft sm:flex">
          {{ __('Back') }}

          <kbd class="rounded-sm border border-hairline-strong bg-canvas px-1.25 font-sans text-[10.5px]">{{ __('esc') }}</kbd>
        </span>
      </a>

      <main id="main" tabindex="-1" class="mx-4 focus:outline-none min-w-0 flex-1 rounded-t-xl border border-b-0 border-hairline-strong bg-page">
        <!-- the bar of the layer -->
        <header class="sticky top-0 z-5 flex h-13.5 items-center gap-3.5 rounded-t-xl border-b border-hairline-soft bg-page/90 pr-5.5 pl-4.5 backdrop-blur-md">
          <a
            href="{{ $backUrl }}"
            data-turbo="true"
            aria-label="{{ __('Go back to :screen', ['screen' => $backTitle]) }}"
            class="flex size-6.5 shrink-0 items-center justify-center rounded-md border border-hairline bg-canvas text-body transition-colors hover:bg-hover hover:text-ink"
          >
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <line x1="13" y1="8" x2="3" y2="8"></line>
              <path d="M6.6 4.4 3 8l3.6 3.6"></path>
            </svg>
          </a>

          @isset($header)
            {{ $header }}
          @endisset

          @isset($actions)
            <div class="ml-auto">{{ $actions }}</div>
          @endisset
        </header>

        {{ $slot }}
      </main>
    </div>

    <x-toaster />
  </body>
</html>

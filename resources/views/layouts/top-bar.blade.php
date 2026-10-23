{{-- The shell of a screen that carries its navigation across the top rather than down the side. --}}
{{--
  @var string|null $title
  @var bool $wide
  @var string $companyName
  @var string $name
  @var \App\Models\Staff|null $staff
  @var string|null $clientsUrl
  @var string|null $servicesUrl
  @var string|null $teamUrl
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <a href="#main" class="sr-only z-80 rounded-[10px] bg-primary px-4 py-2.5 text-[15px] font-semibold text-on-primary focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:ring-2 focus:ring-focus-ring focus:outline-none">{{ __('Skip to content') }}</a>

    <div
      x-data="{ menuOpen: false }"
      @keydown.escape.window="menuOpen = false"
      @keydown.g.window="if (! /input|textarea/i.test($event.target.tagName)) { $event.preventDefault(); menuOpen = ! menuOpen }"
      class="min-h-screen pb-22.5"
    >
      <div class="px-4 sm:px-8">
        <x-top-bar :company-name="$companyName" :name="$name" :staff="$staff" :clients-url="$clientsUrl" :services-url="$servicesUrl" :team-url="$teamUrl" />
      </div>

      <div @class(['mx-auto px-4 sm:px-8', 'max-w-265' => ! $wide])>
        <main id="main" tabindex="-1" class="focus:outline-none">
          {{ $slot }}
        </main>
      </div>
    </div>

    <x-toaster />
  </body>
</html>

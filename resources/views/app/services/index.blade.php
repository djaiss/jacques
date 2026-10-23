{{-- The catalogue of the salon, one card per category, for every salon or for one of them. --}}
{{-- @var \App\ViewModels\Services\ServicesViewModel $viewModel --}}
@php
  $categories = $viewModel->categories();
  $columns = 'lg:grid-cols-[minmax(0,1.5fr)_150px_80px_minmax(0,1fr)_10px] xl:grid-cols-[minmax(0,2fr)_90px_170px_minmax(0,1.3fr)_minmax(0,1.3fr)_10px]';
@endphp

<x-top-bar-layout :title="__('Services')">
  <x-breadcrumb :trail="$viewModel->breadcrumb()" />

  <!-- page title -->
  <div class="space-y-2.5">
    <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] text-ink">{{ __('Services') }}</h1>
    <p class="text-lg text-pretty text-body">{{ $viewModel->lead() }}</p>
  </div>

  @if ($viewModel->isEmpty())
    <!-- empty catalogue -->
    <div class="mx-auto mt-10 grid max-w-160 justify-items-center gap-3.5 rounded-[18px] bg-canvas px-6 py-12 text-center ring-[1.5px] ring-hairline sm:px-10">
      <span class="accent-tile grid size-12 place-items-center rounded-[13px]" style="--tile-hue: 80" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6.5" cy="7" r="2.5"></circle><circle cx="6.5" cy="17" r="2.5"></circle><path d="M8.6 8.4 20 17"></path><path d="M8.6 15.6 20 7"></path></svg>
      </span>
      <h2 class="mt-1 text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('No services yet') }}</h2>
      <p class="max-w-115 text-[15px] leading-relaxed text-pretty text-body">{{ __('Services are what clients book. Once you add one, give it a duration and a price, then pick the salons that offer it and the people who do it.') }}</p>

      @if ($viewModel->canManage())
        <div class="mt-2.5 flex flex-wrap justify-center gap-2.5 max-sm:w-full">
          @if ($viewModel->canLoadReadyMadeCatalogue())
            <x-form method="post" :action="$viewModel->readyMadeCatalogueUrl()" class="max-sm:w-full">
              <x-button.secondary class="h-10 max-sm:w-full">{{ __('Start from a ready-made catalog') }}</x-button.secondary>
            </x-form>
          @endif
          <x-button :href="$viewModel->newServiceUrl()" class="h-10 max-sm:w-full">{{ __('Add a service') }}</x-button>
        </div>
      @endif
    </div>
  @else
    <!-- search and the add buttons -->
    <div class="mt-8 mb-7 flex flex-wrap items-center gap-3">
      <form method="get" action="{{ $viewModel->searchUrl() }}" role="search" class="w-full sm:w-65">
        <label for="services-search" class="sr-only">{{ __('Find a service') }}</label>
        <input
          id="services-search"
          type="search"
          name="q"
          value="{{ $viewModel->search() }}"
          placeholder="{{ __('Find a service') }}"
          class="block h-10 w-full rounded-[10px] border-[1.5px] border-control bg-input px-3.25 text-base text-ink placeholder:text-placeholder hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
        />
      </form>

      <div class="hidden flex-1 sm:block"></div>

      @if ($viewModel->canManage())
        <x-button.secondary :href="$viewModel->newCategoryUrl()" class="h-10 max-sm:flex-1">{{ __('Add a category') }}</x-button.secondary>
        <x-button :href="$viewModel->newServiceUrl()" class="h-10 max-sm:flex-1">{{ __('Add a service') }}</x-button>
      @endif
    </div>

    <div class="grid items-start gap-10 lg:grid-cols-[180px_minmax(0,1fr)] xl:grid-cols-[220px_minmax(0,1fr)]">
      <!-- salons and categories -->
      <nav class="grid gap-7 lg:sticky lg:top-6" aria-label="{{ __('Salons and categories') }}">
        <div class="space-y-2">
          <p class="pl-3 text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Salons') }}</p>
          <div class="grid gap-0.5 max-lg:grid-cols-[repeat(auto-fill,minmax(180px,1fr))]">
            @foreach ($viewModel->salons() as $salon)
              <a
                href="{{ $salon['url'] }}"
                @if ($salon['isCurrent']) aria-current="page" @endif
                class="grid gap-0.5 rounded-[10px] border px-3 py-2 transition-colors focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none {{ $salon['isCurrent'] ? 'border-nav-active-border bg-nav-active' : 'border-transparent hover:border-nav-hover-border hover:bg-nav-hover' }}"
              >
                <span class="text-[15px] font-semibold text-ink">{{ $salon['label'] }}</span>
                <span class="text-[13px] text-muted">{{ $salon['text'] }}</span>
              </a>
            @endforeach
          </div>
        </div>

        @if ($categories !== [])
          <div class="space-y-2">
            <p class="pl-3 text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Categories') }}</p>
            <div class="grid gap-0.5 max-lg:grid-cols-[repeat(auto-fill,minmax(180px,1fr))]">
              @foreach ($categories as $category)
                <a href="#{{ $category['key'] }}" class="flex items-center justify-between gap-2.5 rounded-[10px] px-3 py-2 text-[15px] font-semibold text-ink transition-colors hover:bg-nav-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">
                  <span class="min-w-0 truncate">{{ $category['name'] }}</span>
                  <span class="text-[13px] text-muted tabular-nums">{{ $category['count'] }}</span>
                </a>
              @endforeach
            </div>
          </div>
        @endif
      </nav>

      <div class="grid min-w-0 gap-6">
        @if ($viewModel->noResults())
          <!-- no result -->
          <div class="rounded-[18px] bg-canvas p-10 text-center text-[15px] text-body ring-[1.5px] ring-hairline">{{ __('No service is called that. Check the spelling, or add it as a new service.') }}</div>
        @endif

        @foreach ($categories as $category)
          <!-- category card -->
          <section id="{{ $category['key'] }}" class="scroll-mt-6 overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline" aria-labelledby="{{ $category['key'] }}-title">
            <div class="flex items-center gap-4 px-6 py-5">
              <span class="accent-tile grid size-9.5 shrink-0 place-items-center rounded-xl text-[15px] font-bold" style="--tile-hue: {{ $category['hue'] }}" aria-hidden="true">{{ $category['count'] }}</span>
              <h2 id="{{ $category['key'] }}-title" class="min-w-0 flex-1 text-[22px] font-bold tracking-[-0.025em] text-ink">
                @if ($category['editUrl'])
                  <a href="{{ $category['editUrl'] }}" class="hover:text-brand focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none" aria-label="{{ __('Edit the category :name', ['name' => $category['name']]) }}">{{ $category['name'] }}</a>
                @else
                  {{ $category['name'] }}
                @endif
              </h2>
              @if ($category['addUrl'])
                <a href="{{ $category['addUrl'] }}" class="flex shrink-0 items-center gap-2 rounded-lg px-2 py-1.5 text-[15px] font-semibold text-body transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">
                  <span class="text-lg leading-none" aria-hidden="true">+</span>
                  {{ __('Add a service') }}
                  <span class="sr-only">{{ __('in :category', ['category' => $category['name']]) }}</span>
                </a>
              @endif
            </div>

            @if ($category['rows'] === [])
              <p class="border-t border-hairline px-6 py-4 text-[15px] text-muted">{{ __('No services in this category yet.') }}</p>
            @else
              <div class="hidden gap-4 border-t border-hairline bg-sunken px-6 py-2.5 text-xs font-bold tracking-[0.08em] text-muted uppercase lg:grid {{ $columns }}" aria-hidden="true">
                <span>{{ __('Service') }}</span>
                <span class="hidden text-right xl:block">{{ __('Duration') }}</span>
                <span class="text-right">{{ __('Price') }}</span>
                <span>{{ __('Salons') }}</span>
                <span>{{ $viewModel->peopleHeading() }}</span>
                <span></span>
              </div>

              <ul>
                @foreach ($category['rows'] as $row)
                  @include('app.services._row', ['row' => $row, 'columns' => $columns])
                @endforeach
              </ul>
            @endif
          </section>
        @endforeach
      </div>
    </div>
  @endif
</x-top-bar-layout>

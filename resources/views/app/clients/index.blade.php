{{-- Every client of the account, by segment, searchable and narrowed to one salon. --}}
{{-- @var \App\ViewModels\Clients\ClientsViewModel $viewModel --}}
@php
  $rows = $viewModel->rows();
  $paginator = $viewModel->paginator();
  $columns = 'md:grid-cols-[minmax(0,1fr)_120px_120px_130px_56px]';
@endphp

<x-top-bar-layout :title="__('Clients')">
  <x-breadcrumb :trail="$viewModel->breadcrumb()" />

  <!-- page title -->
  <div class="space-y-2.5">
    <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] text-ink">{{ __('Clients') }}</h1>
    <p class="max-w-170 text-lg text-pretty text-body">{{ __('Everybody who has booked at one of your salons. A client belongs to the account, so the same person can be seen in several salons.') }}</p>
  </div>

  <div class="mt-7 space-y-4">
    <!-- search and salon -->
    <form method="get" action="{{ $viewModel->searchUrl() }}" role="search" class="flex flex-wrap gap-2.5">
      <div class="relative min-w-0 flex-[1_1_320px]">
        <label for="clients-search" class="sr-only">{{ __('Search by name, phone or email') }}</label>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-muted" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="M20 20l-4.2-4.2"></path></svg>
        <input
          id="clients-search"
          type="search"
          name="q"
          value="{{ $viewModel->search() }}"
          placeholder="{{ __('Search by name, phone or email') }}"
          class="block h-11 w-full rounded-[10px] border-[1.5px] border-control bg-input pr-3.25 pl-10 text-base text-ink placeholder:text-placeholder hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
        />
      </div>

      <div class="min-w-50 flex-[0_1_240px]">
        <label for="clients-salon" class="sr-only">{{ __('Salon') }}</label>
        <select
          id="clients-salon"
          name="location"
          x-data
          @change="$el.form.requestSubmit()"
          class="block h-11 w-full cursor-pointer rounded-[10px] border-[1.5px] border-control bg-input px-3.25 text-base text-ink hover:border-ink focus:border-focus-ring focus:ring-2 focus:ring-focus-ring focus:outline-none"
        >
          <option value="">{{ __('All salons') }}</option>
          @foreach ($viewModel->locations() as $location)
            <option value="{{ $location['id'] }}" @selected($location['isCurrent'])>{{ $location['name'] }}</option>
          @endforeach
        </select>
      </div>

      <x-button.secondary type="submit" class="h-11 max-sm:flex-1">{{ __('Search') }}</x-button.secondary>
    </form>

    <!-- segments -->
    <div class="space-y-2.5">
      <nav aria-label="{{ __('Segments') }}" class="flex flex-wrap gap-1 self-start rounded-xl border border-hairline bg-sunken p-1 sm:inline-flex">
        @foreach ($viewModel->tabs() as $tab)
          <a
            href="{{ $tab['url'] }}"
            @if ($tab['isCurrent']) aria-current="page" @endif
            class="flex items-center gap-1.5 rounded-lg border px-3 py-1.75 text-sm font-medium text-ink transition-colors focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none {{ $tab['isCurrent'] ? 'border-hairline-strong bg-canvas' : 'border-transparent hover:bg-hover' }}"
          >
            @if ($tab['icon'] !== '')
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $tab['icon'] !!}</svg>
            @endif
            <span>{{ $tab['label'] }}</span>
            <span class="font-normal text-muted tabular-nums">{{ $tab['count'] }}</span>
          </a>
        @endforeach
      </nav>

      <p class="text-sm text-pretty text-body">{{ $viewModel->description() }}</p>
    </div>

    <!-- client list -->
    <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
      <div class="hidden gap-4 border-b border-hairline-soft px-6 py-3 text-xs font-bold tracking-[0.08em] text-muted uppercase md:grid {{ $columns }}" aria-hidden="true">
        <span>{{ __('Client') }}</span>
        <span>{{ __('Salon') }}</span>
        <span>{{ __('Last visit') }}</span>
        <span>{{ __('Next appointment') }}</span>
        <span class="text-right">{{ __('Visits') }}</span>
      </div>

      @if ($rows === [])
        <p class="px-6 py-10 text-center text-sm text-muted">{{ $viewModel->emptyText() }}</p>
      @else
        <ul>
          @foreach ($rows as $row)
            @include('app.clients._row', ['row' => $row, 'columns' => $columns])
          @endforeach
        </ul>
      @endif
    </div>

    <!-- count and pages -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-muted">
      <p>{{ $viewModel->footer() }}</p>

      @if ($paginator->hasPages())
        <nav aria-label="{{ __('Pages') }}" class="flex items-center gap-3">
          @if ($paginator->previousPageUrl() !== null)
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="font-medium text-ink hover:text-brand">{{ __('Previous') }}</a>
          @endif
          <span>{{ __('Page :page of :pages', ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage()]) }}</span>
          @if ($paginator->nextPageUrl() !== null)
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="font-medium text-ink hover:text-brand">{{ __('Next') }}</a>
          @endif
        </nav>
      @endif
    </div>
  </div>
</x-top-bar-layout>

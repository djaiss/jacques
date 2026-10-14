{{-- The bar above the list: which list to read, what to search for, and the order. --}}
{{-- @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel --}}
@php
  $sort = $viewModel->sortToggle();
@endphp

<div class="mb-4 flex flex-wrap items-center gap-2.5">
  <!-- open, archived or all -->
  <nav aria-label="{{ __('Which locations') }}" class="flex gap-0.75 rounded-xl bg-canvas p-1 ring-[1.5px] ring-hairline">
    @foreach ($viewModel->scopes() as $scope)
      <a
        href="{{ $scope['url'] }}"
        data-turbo="true"
        @if ($scope['current']) aria-current="page" @endif
        class="rounded-[9px] px-3.5 py-2 text-[15px] font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none {{ $scope['current'] ? 'bg-nav-hover text-ink' : 'text-muted hover:text-ink' }}"
      >
        {{ $scope['label'] }}
      </a>
    @endforeach
  </nav>

  <!-- search box -->
  <form method="get" action="{{ url()->current() }}" class="min-w-55 flex-1">
    <label for="q" class="sr-only">{{ __('Search the locations') }}</label>

    @foreach ($viewModel->sortState() as $field => $value)
      <input type="hidden" name="{{ $field }}" value="{{ $value }}" />
    @endforeach

    <input
      type="search"
      id="q"
      name="q"
      value="{{ $viewModel->search() }}"
      placeholder="{{ __('Search a location, a city or a country') }}"
      class="block w-full appearance-none rounded-xl border-[1.5px] border-control bg-input px-3.5 py-2.5 text-base text-ink placeholder-placeholder transition-colors duration-150 hover:border-ink hover:bg-hover focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
    />
  </form>

  <a
    href="{{ $sort['url'] }}"
    data-turbo="true"
    aria-label="{{ $sort['description'] }}"
    class="rounded-xl bg-canvas px-4 py-2.5 text-[15px] font-semibold whitespace-nowrap text-body ring-[1.5px] ring-hairline transition-colors hover:text-ink hover:ring-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
  >
    {{ $sort['label'] }}
  </a>
</div>

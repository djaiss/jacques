{{-- The people who work at the salon, whether they sign in, and the panel that manages one of them. --}}
{{-- @var \App\ViewModels\Settings\Administration\TeamViewModel $viewModel --}}
<x-top-bar-layout :title="__('Team members')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Team members') => null,
    ]"
  />

  <!-- page title -->
  <h1 class="mb-7 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Team members') }}</h1>

  <div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <!-- team tabs -->
      <nav aria-label="{{ __('Filter the team') }}" class="flex flex-wrap gap-1 rounded-xl border border-hairline bg-sunken p-1">
        @foreach ($viewModel->tabs() as $tab)
          <a
            href="{{ $tab['url'] }}"
            data-turbo="true"
            @if ($tab['isCurrent']) aria-current="page" @endif
            class="flex gap-1.5 rounded-lg border px-3 py-1.75 text-sm font-medium text-ink transition-colors hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none {{ $tab['isCurrent'] ? 'border-hairline-strong bg-canvas' : 'border-transparent' }}"
          >
            <span>{{ $tab['label'] }}</span>
            <span class="font-normal text-muted tabular-nums">{{ $tab['count'] }}</span>
          </a>
        @endforeach
      </nav>

      <p class="text-sm text-muted">{{ __('Only owners can change who sees what.') }}</p>
    </div>

    <!-- team list -->
    <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
      @if ($viewModel->rows() === [])
        <!-- empty tab -->
        <p class="px-6 py-10 text-center text-sm text-muted">{{ __('Nobody matches this filter right now.') }}</p>
      @else
        <div class="hidden grid-cols-[minmax(0,1fr)_170px_210px_104px] gap-5 border-b border-hairline-soft px-6 py-3 text-xs font-bold tracking-[0.08em] text-muted uppercase md:grid" aria-hidden="true">
          <span>{{ __('Name') }}</span>
          <span>{{ __('Role') }}</span>
          <span>{{ __('Account') }}</span>
          <span></span>
        </div>

        <ul>
          @foreach ($viewModel->rows() as $row)
            @include('app.settings.administration.team._row', ['row' => $row])
          @endforeach
        </ul>
      @endif
    </div>
  </div>

  <div id="team-panel">
    @if ($viewModel->panel() !== null)
      <div id="team-panel-{{ $viewModel->panel()['id'] }}">
        @include('app.settings.administration.team._panel', ['panel' => $viewModel->panel(), 'viewModel' => $viewModel])
      </div>
    @else
      <div id="team-panel-closed" x-init="document.body.classList.remove('overflow-hidden')"></div>
    @endif
  </div>
</x-top-bar-layout>

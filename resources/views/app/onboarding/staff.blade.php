{{-- The third onboarding step, where a new salon lists the people who work there and what each one does. --}}
{{-- @var \App\ViewModels\Onboarding\TeamViewModel $viewModel --}}
<x-top-bar-layout :title="__('Your team')">
  @include('app.onboarding._header', [
    'viewModel' => $viewModel,
    'title' => $viewModel->title(),
    'introduction' => $viewModel->introduction(),
  ])

  <div class="mt-7 space-y-7">
    <div id="team-list" class="space-y-4">
      <!-- team heading -->
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="space-y-1">
          <h2 class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Your team') }}</h2>
          <p class="text-sm text-body">{{ $viewModel->summary() }}</p>
        </div>

        <x-button :href="$viewModel->newStaffUrl()" x-target="staff-panel">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
          {{ __('Add a team member') }}
        </x-button>
      </div>

      <!-- team list -->
      <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
        <ul>
          @foreach ($viewModel->rows() as $row)
            @include('app.onboarding._team-row', ['row' => $row])
          @endforeach
        </ul>

        <a href="{{ $viewModel->newStaffUrl() }}" x-target="staff-panel" class="flex w-full items-center gap-2 px-4.5 py-3.5 text-sm font-semibold text-body transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
          {{ __('Add a team member') }}
        </a>
      </div>
    </div>

    @include('app.onboarding._team-footer', ['viewModel' => $viewModel])

    <div id="staff-panel">
      @if ($viewModel->panel() !== null)
        <div id="staff-panel-{{ $viewModel->panel()->key() }}">
          @include('app.onboarding._staff-panel', ['panel' => $viewModel->panel()])
        </div>
      @else
        <div id="staff-panel-closed" x-init="document.body.classList.remove('overflow-hidden')"></div>
      @endif
    </div>
  </div>
</x-top-bar-layout>

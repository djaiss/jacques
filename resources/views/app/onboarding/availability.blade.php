{{-- The last onboarding step, where a new salon sets its opening hours and the hours its team works. --}}
{{-- @var \App\ViewModels\Onboarding\AvailabilityViewModel $viewModel --}}
<x-top-bar-layout :title="__('Availability')">
  @include('app.onboarding._header', [
    'viewModel' => $viewModel,
    'title' => $viewModel->title(),
    'introduction' => $viewModel->introduction(),
  ])

  <div class="mt-10 space-y-10" x-data="openingHoursEditor({{ Js::from([...$viewModel->editor(), 'useOpeningHours' => $viewModel->isUsingOpeningHours()]) }})">
    @include('app.onboarding._opening-hours', ['viewModel' => $viewModel])

    @include('app.onboarding._team-hours', ['viewModel' => $viewModel])

    @include('app.onboarding._availability-footer', ['viewModel' => $viewModel])

    <div id="hours-panel">
      @if ($viewModel->panel() !== null)
        <div id="hours-panel-{{ $viewModel->panel()->key() }}">
          @include('app.onboarding._hours-panel', ['panel' => $viewModel->panel()])
        </div>
      @else
        <div id="hours-panel-closed" x-init="document.body.classList.remove('overflow-hidden')"></div>
      @endif
    </div>
  </div>
</x-top-bar-layout>

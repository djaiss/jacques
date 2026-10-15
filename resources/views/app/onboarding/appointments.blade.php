{{-- The last onboarding step, for the first appointments, a placeholder until it is designed. --}}
{{-- @var \App\ViewModels\Onboarding\StepViewModel $viewModel --}}
<x-top-bar-layout :title="__('Appointments')">
  @include('app.onboarding._header', ['viewModel' => $viewModel])

  <!-- step placeholder -->
  <div class="mt-7 max-w-180 space-y-4.5 rounded-[18px] bg-canvas p-7 ring-[1.5px] ring-hairline">
    <div class="space-y-1.5">
      <h2 class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Appointments') }}</h2>
      <p class="text-sm leading-normal text-pretty text-body">{{ __('This step is coming soon. You can move on for now.') }}</p>
    </div>

    <x-form method="put" :action="route('onboarding.appointments.update')">
      <x-button>{{ __('Finish') }}</x-button>
    </x-form>
  </div>
</x-top-bar-layout>

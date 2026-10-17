{{-- The bar under the hours: what is ready, what still needs fixing, and the way out of the onboarding. --}}
{{-- @var \App\ViewModels\Onboarding\AvailabilityViewModel $viewModel --}}
<!-- availability footer -->
<div id="availability-footer" x-merge="replace" class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 border-t border-hairline pt-5">
  <p class="min-w-0 text-sm" role="status" aria-live="polite" :class="hasErrors(days) ? 'text-error' : 'text-body'">
    <span x-show="hasErrors(days)" x-cloak>{{ __('Fix the opening hours marked in red to finish.') }}</span>
    <span x-show="! hasErrors(days) && openDayCount() === 0" x-cloak>{{ __('Clients cannot book until a day is open.') }}</span>
    <span x-show="! hasErrors(days) && openDayCount() > 0 && useOpeningHours">{{ $viewModel->teamOnOpeningHoursStatus() }}</span>
    <span x-show="! hasErrors(days) && openDayCount() > 0 && ! useOpeningHours" x-cloak>{{ $viewModel->teamStatus() }}</span>
  </p>

  <div class="w-full space-y-2 sm:w-auto">
    <x-button form="availability-form" x-bind:disabled="hasErrors(days)" class="w-full sm:w-auto">
      {{ __('Finish setup') }}
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"></path></svg>
    </x-button>
    <x-error :messages="$errors->get('days')" />
  </div>
</div>

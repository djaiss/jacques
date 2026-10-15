{{-- The bar under the team: the invitations prepared, and the ways on to the next step. --}}
{{-- @var \App\ViewModels\Onboarding\TeamViewModel $viewModel --}}
<!-- team footer -->
<div id="team-footer" class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 border-t border-hairline pt-5">
  <p class="min-w-0 text-sm text-body" role="status" aria-live="polite">{{ $viewModel->footerStatus() }}</p>

  <x-form method="put" :action="route('onboarding.staff.update')" class="flex w-full flex-wrap gap-2.5 sm:w-auto">
    <x-button.secondary class="flex-1 sm:flex-none">{{ __('I’ll set this up later') }}</x-button.secondary>
    <x-button class="flex-1 sm:flex-none">
      {{ __('Continue to appointments') }}
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"></path></svg>
    </x-button>
  </x-form>
</div>

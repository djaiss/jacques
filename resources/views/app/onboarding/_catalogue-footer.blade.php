{{-- The bar under the catalogue: what was just saved or removed, and the ways on to the next step. --}}
{{-- @var \App\ViewModels\Onboarding\ServicesViewModel $viewModel --}}
<!-- catalogue footer -->
<div id="catalogue-footer" class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 border-t border-hairline pt-5">
  <div class="flex min-w-0 flex-wrap items-center gap-2.5 text-sm text-body" role="status" aria-live="polite">
    @if ($viewModel->removalMessage() !== null)
      <span>{{ $viewModel->removalMessage() }}</span>
      <x-form method="post" :action="route('onboarding.services.restoration.create')" x-target="catalogue catalogue-footer">
        <button type="submit" class="cursor-pointer px-0.5 py-1 text-sm font-semibold text-ink underline underline-offset-3 transition-colors hover:text-brand">{{ __('Undo') }}</button>
      </x-form>
    @else
      @if ($viewModel->isSaved())
        <span id="catalogue-saved-{{ uniqid() }}" x-data="{ shown: true }" x-init="setTimeout(() => shown = false, 2000)" class="flex items-center gap-2">
          <span x-show="shown" class="flex items-center gap-2 text-success">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7"></path></svg>
            {{ __('Saved') }}
          </span>
          <span x-show="! shown" x-cloak>{{ $viewModel->footerStatus() }}</span>
        </span>
      @else
        <span>{{ $viewModel->footerStatus() }}</span>
      @endif
    @endif
  </div>

  <x-form method="put" :action="route('onboarding.services.update')" class="flex w-full flex-wrap gap-2.5 sm:w-auto">
    <x-button.secondary class="flex-1 sm:flex-none">{{ __('I’ll set this up later') }}</x-button.secondary>
    <x-button class="flex-1 sm:flex-none">
      {{ __('Continue to your team') }}
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"></path></svg>
    </x-button>
  </x-form>
</div>

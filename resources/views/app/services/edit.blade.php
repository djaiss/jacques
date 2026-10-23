{{-- The page that edits a service of the catalogue, and archives it. --}}
{{-- @var \App\ViewModels\Services\ServiceFormViewModel $viewModel --}}
<x-top-bar-layout :title="$viewModel->title()">
  <x-breadcrumb :trail="$viewModel->breadcrumb()" />

  <!-- page title and the save buttons -->
  <div class="mb-10 flex flex-wrap items-end gap-4">
    <div class="min-w-70 flex-1 space-y-2">
      <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ $viewModel->eyebrow() }}</p>
      <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] wrap-anywhere text-ink">{{ $viewModel->title() }}</h1>
    </div>

    <div class="flex gap-2.5 max-sm:w-full">
      <x-button.secondary :href="$viewModel->cancelUrl()" class="h-10 max-sm:flex-1">{{ __('Cancel') }}</x-button.secondary>
      <x-button form="service-form" class="h-10 max-sm:flex-1">{{ $viewModel->saveLabel() }}</x-button>
    </div>
  </div>

  <div class="space-y-10">
    @include('app.services._form', ['viewModel' => $viewModel])

    @if (! $viewModel->isArchived())
      <!-- archive -->
      <section class="space-y-3.5" aria-labelledby="service-archive-title" x-data="{ asking: false }">
        <h2 id="service-archive-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Archive') }}</h2>
        <div class="flex flex-wrap items-center gap-4 rounded-[18px] bg-canvas px-6 py-5 ring-[1.5px] ring-hairline">
          <template x-if="! asking">
            <div class="contents">
              <p class="min-w-60 flex-1 text-[15px] text-body">{{ __('Clients can no longer book it. Past appointments keep it in their history.') }}</p>
              <x-button.secondary type="button" x-on:click="asking = true; $nextTick(() => $refs.keep.focus())" class="h-10">{{ __('Archive this service') }}</x-button.secondary>
            </div>
          </template>
          <template x-if="asking">
            <div class="contents">
              <p class="min-w-60 flex-1 text-[15px] text-ink" role="alert">{{ __('The service leaves the catalog and nobody can book it. Sure?') }}</p>
              <x-button.secondary type="button" x-ref="keep" x-on:click="asking = false" class="h-10">{{ __('Keep it') }}</x-button.secondary>
              <x-form method="post" :action="$viewModel->archiveUrl()">
                <x-button.danger class="h-10">{{ __('Archive it') }}</x-button.danger>
              </x-form>
            </div>
          </template>
        </div>
      </section>
    @endif
  </div>
</x-top-bar-layout>

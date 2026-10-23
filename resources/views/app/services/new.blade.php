{{-- The page that adds a service to the catalogue. --}}
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

  @include('app.services._form', ['viewModel' => $viewModel])
</x-top-bar-layout>

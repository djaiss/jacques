{{-- The page that adds a category to the catalogue. --}}
{{-- @var \App\ViewModels\Services\ServiceCategoryFormViewModel $viewModel --}}
<x-top-bar-layout :title="$viewModel->title()">
  <x-breadcrumb :trail="$viewModel->breadcrumb()" />

  <!-- page title -->
  <div class="mb-10 space-y-2">
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Add a category') }}</p>
    <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] text-ink">{{ $viewModel->title() }}</h1>
    <p class="text-lg text-pretty text-body">{{ __('A category groups services on the list and on the booking page. It starts empty.') }}</p>
  </div>

  <div class="max-w-2xl">
    @include('app.services.categories._form', ['viewModel' => $viewModel])
  </div>
</x-top-bar-layout>

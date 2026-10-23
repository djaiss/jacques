{{-- The page that renames a category of the catalogue, or removes it. --}}
{{-- @var \App\ViewModels\Services\ServiceCategoryFormViewModel $viewModel --}}
<x-top-bar-layout :title="$viewModel->title()">
  <x-breadcrumb :trail="$viewModel->breadcrumb()" />

  <!-- page title -->
  <div class="mb-10 space-y-2">
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Edit category') }}</p>
    <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] wrap-anywhere text-ink">{{ $viewModel->title() }}</h1>
  </div>

  <div class="max-w-2xl space-y-10">
    @include('app.services.categories._form', ['viewModel' => $viewModel])

    <!-- remove the category -->
    <section class="space-y-3.5" aria-labelledby="category-remove-title" x-data="{ confirming: false }">
      <h2 id="category-remove-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Remove') }}</h2>
      <div class="flex flex-wrap items-center gap-4 rounded-[18px] bg-canvas px-6 py-5 ring-[1.5px] ring-hairline">
        <p class="min-w-60 flex-1 text-[15px] text-body">{{ $viewModel->removalWarning() }}</p>
        <x-button.secondary type="button" x-on:click="confirming = true" class="h-10">{{ __('Remove this category') }}</x-button.secondary>
      </div>

      <x-confirm-dialog show="confirming" close="confirming = false" labelledby="category-remove-dialog-title" :title="__('Remove :name?', ['name' => $viewModel->title()])" :cancel="__('Keep it')">
        {{ $viewModel->removalWarning() }}

        <x-slot:actions>
          <x-form method="delete" :action="$viewModel->destroyUrl()">
            <x-button.danger>{{ __('Remove the category') }}</x-button.danger>
          </x-form>
        </x-slot>
      </x-confirm-dialog>
    </section>
  </div>
</x-top-bar-layout>

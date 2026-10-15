{{-- The second onboarding step, where a new salon shapes the catalogue of services it offers. --}}
{{-- @var \App\ViewModels\Onboarding\ServicesViewModel $viewModel --}}
@php
  $languageLabels = collect($viewModel->languages()->cards())->mapWithKeys(fn (array $card): array => [$card['code'] => ['label' => $card['label'], 'flag' => $card['flag']]])->all();
@endphp

<x-top-bar-layout :title="__('Your services')">
  @include('app.onboarding._header', [
    'viewModel' => $viewModel,
    'title' => __('What services do you offer?'),
    'introduction' => __('We have prepared a few popular services to get you started. Adjust the names, prices and durations to fit your salon.'),
  ])

  <div class="mt-7 space-y-7" x-data="{ language: @js($viewModel->firstLanguage()), languages: @js($languageLabels) }">
    <div id="catalogue" class="space-y-7">
      @if ($viewModel->hasSeveralLanguages())
        @include('app.onboarding._catalogue-languages', ['viewModel' => $viewModel])
      @endif

      <div class="flex flex-wrap items-start gap-7">
        <div class="min-w-0 flex-[1_1_560px] space-y-4">
          <!-- catalogue heading -->
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2.5">
                <h2 class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Your catalog') }}</h2>
                @if ($viewModel->hasSeveralLanguages())
                  <span class="flex items-center gap-1.5 text-sm text-body">
                    <span class="block h-3 w-4.5 rounded-xs ring-1 ring-black/10" x-bind:style="`background: ${languages[language].flag}`" aria-hidden="true"></span>
                    <span x-text="languages[language].label"></span>
                  </span>
                @endif
              </div>
              <p class="text-sm text-body">{{ $viewModel->summary() }}</p>
              @if ($viewModel->hasCatalogue() && $viewModel->missingPriceSummary() !== '')
                <p class="text-sm font-semibold text-warning">{{ $viewModel->missingPriceSummary() }}</p>
              @endif
            </div>

            <div class="flex flex-wrap items-center gap-2.5" x-data="{ confirmingEmpty: false }">
              @if ($viewModel->hasCatalogue())
                <button type="button" x-on:click="confirmingEmpty = true" class="cursor-pointer px-1 py-2 text-sm text-muted underline underline-offset-3 transition-colors hover:text-ink">{{ __('Start with an empty catalog') }}</button>

                <x-confirm-dialog show="confirmingEmpty" close="confirmingEmpty = false" labelledby="empty-catalogue-title" :title="__('Start with an empty catalog?')" :cancel="__('Keep the catalog')">
                  {{ trans_choice('Every category and its services go, :count service in all. You can undo it right after.|Every category and its services go, :count services in all. You can undo it right after.', $viewModel->serviceCount()) }}

                  <x-slot:actions>
                    <x-form method="delete" :action="route('onboarding.services.catalogue.destroy')" x-target="catalogue catalogue-footer service-panel" x-on:submit="confirmingEmpty = false">
                      <x-button.danger>{{ __('Empty the catalog') }}</x-button.danger>
                    </x-form>
                  </x-slot>
                </x-confirm-dialog>
              @endif

              <x-form method="post" :action="route('onboarding.services.categories.create')" x-target="catalogue catalogue-footer">
                <x-button.secondary class="py-2">{{ __('Add a category') }}</x-button.secondary>
              </x-form>
            </div>
          </div>

          @if (! $viewModel->hasCatalogue())
            <!-- empty catalogue -->
            <div class="flex flex-col items-center space-y-3.5 rounded-[18px] bg-canvas px-7 py-11 text-center ring-[1.5px] ring-hairline">
              <span class="accent-tile flex size-11.5 items-center justify-center rounded-xl" style="--tile-hue: 80" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="7" r="2.5"></circle><circle cx="6" cy="17" r="2.5"></circle><path d="M8 8.5L20 17M8 15.5L20 7"></path></svg>
              </span>
              <h3 class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Your catalog is empty') }}</h3>
              <p class="max-w-105 text-sm leading-normal text-pretty text-body">{{ __('Add the services your salon offers, one category at a time. You can also bring back the example catalog and keep only what you need.') }}</p>
              <div class="flex flex-wrap justify-center gap-2.5 pt-1.5">
                <x-form method="post" :action="route('onboarding.services.categories.create')" x-target="catalogue catalogue-footer">
                  <x-button>{{ __('Add a category') }}</x-button>
                </x-form>
                <x-form method="post" :action="route('onboarding.services.catalogue.create')" x-target="catalogue catalogue-footer">
                  <x-button.secondary>{{ __('Use the example catalog') }}</x-button.secondary>
                </x-form>
              </div>
            </div>
          @endif

          @foreach ($viewModel->categories() as $category)
            @include('app.onboarding._catalogue-category', ['category' => $category, 'viewModel' => $viewModel])
          @endforeach

          @if ($viewModel->uncategorizedServices() !== [])
            <!-- services without a category -->
            <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
              <h3 class="border-b border-hairline-soft px-5 py-3.5 text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Without a category') }}</h3>
              @foreach ($viewModel->uncategorizedServices() as $service)
                @include('app.onboarding._service-row', ['service' => $service, 'viewModel' => $viewModel])
              @endforeach
            </div>
          @endif
        </div>

        @include('app.onboarding._steps-explainer')
      </div>
    </div>

    @include('app.onboarding._catalogue-footer', ['viewModel' => $viewModel])

    <div id="service-panel">
      @if ($viewModel->panel() !== null)
        <div id="service-panel-{{ $viewModel->panel()->id() }}">
          @include('app.onboarding._service-panel', ['panel' => $viewModel->panel()])
        </div>
      @else
        <div id="service-panel-closed" x-init="document.body.classList.remove('overflow-hidden')"></div>
      @endif
    </div>
  </div>
</x-top-bar-layout>

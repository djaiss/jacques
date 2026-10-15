{{-- One category of the catalogue, its name editable in place, and the services listed in it. --}}
{{--
  @var array<string, mixed> $category
  @var \App\ViewModels\Onboarding\ServicesViewModel $viewModel
--}}
@php
  $missingLabels = collect($category['missing'])->map(fn (int $count): string => $count === 0 ? '' : trans_choice(':count to translate|:count to translate', $count))->all();
  $iconButton = 'flex size-8 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none disabled:cursor-default disabled:opacity-35 disabled:hover:bg-transparent';
@endphp

<!-- service category -->
<section
  id="category-{{ $category['id'] }}"
  class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline"
  x-data="{ expanded: true, confirmingRemoval: false, missing: @js($missingLabels) }"
  aria-labelledby="category-{{ $category['id'] }}-name"
>
  <div class="flex items-center gap-3 py-3.5 pr-4 pl-3.5">
    <button
      type="button"
      x-on:click="expanded = ! expanded"
      x-bind:aria-expanded="expanded"
      aria-controls="category-{{ $category['id'] }}-services"
      aria-label="{{ __('Show or hide the services of this category') }}"
      class="{{ $iconButton }} group size-7"
    >
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200 group-aria-[expanded=false]:-rotate-90" aria-hidden="true"><path d="M4 6l4 4 4-4"></path></svg>
    </button>

    <span class="accent-tile flex size-8.5 shrink-0 items-center justify-center rounded-[10px] text-sm font-bold tabular-nums" style="--tile-hue: {{ $category['hue'] }}" aria-hidden="true">{{ $category['serviceCount'] }}</span>

    <div class="min-w-0 flex-1 space-y-px">
      <x-form method="put" :action="$category['updateUrl']" x-target="catalogue catalogue-footer" x-on:change="$el.requestSubmit()">
        @foreach ($category['texts'] as $code => $text)
          <label for="category-{{ $category['id'] }}-name-{{ $code }}" class="sr-only">{{ __('Category name') }}</label>
          <input
            id="category-{{ $category['id'] }}-name-{{ $code }}"
            @if ($loop->first) data-category-name="{{ $category['id'] }}" @endif
            type="text"
            name="name[{{ $code }}]"
            value="{{ $text['value'] }}"
            maxlength="255"
            placeholder="{{ $code === $viewModel->firstLanguage() ? __('Name this category') : $category['texts'][$viewModel->firstLanguage()]['shown'] }}"
            x-show="language === @js($code)"
            @if (! $loop->first) x-cloak @endif
            class="-ml-1.75 w-full rounded-lg border border-transparent bg-transparent px-1.5 py-0.75 text-[17px] font-bold tracking-[-0.025em] text-ink placeholder:font-semibold placeholder:text-muted hover:border-hairline-strong focus:border-focus focus:bg-canvas focus:outline-none"
          />
        @endforeach
      </x-form>

      <h3 id="category-{{ $category['id'] }}-name" class="sr-only">{{ $category['texts'][$viewModel->firstLanguage()]['shown'] }}</h3>

      <p class="flex gap-1.5 text-xs text-muted">
        <span>{{ $category['serviceCountLabel'] }}</span>
        <span class="font-semibold text-warning" x-show="language !== @js($viewModel->firstLanguage()) && missing[language] !== ''" x-text="'· ' + missing[language]" x-cloak></span>
      </p>

      @if ($errors->getBag("category_{$category['id']}")->any())
        <p class="text-xs text-error" role="alert">{{ $errors->getBag("category_{$category['id']}")->first() }}</p>
      @endif
    </div>

    <div class="flex shrink-0 items-center gap-0.5">
      <x-form method="put" :action="$category['positionUrl']" x-target="catalogue catalogue-footer">
        <input type="hidden" name="direction" value="up" />
        <button type="submit" class="{{ $iconButton }}" aria-label="{{ __('Move this category up') }}" @disabled($category['isFirst'])>
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 13V3M4 7l4-4 4 4"></path></svg>
        </button>
      </x-form>

      <x-form method="put" :action="$category['positionUrl']" x-target="catalogue catalogue-footer">
        <input type="hidden" name="direction" value="down" />
        <button type="submit" class="{{ $iconButton }}" aria-label="{{ __('Move this category down') }}" @disabled($category['isLast'])>
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3v10M4 9l4 4 4-4"></path></svg>
        </button>
      </x-form>

      @if ($category['serviceCount'] > 0)
        <button type="button" x-on:click="confirmingRemoval = true" class="{{ $iconButton }}" aria-label="{{ __('Remove this category') }}">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.6 8.5h5.8l.6-8.5"></path></svg>
        </button>

        <x-confirm-dialog show="confirmingRemoval" close="confirmingRemoval = false" labelledby="category-{{ $category['id'] }}-removal-title" :title="__('Remove this category?')" :cancel="__('Keep it')">
          {{ trans_choice('“:name” goes with its :count service. You can undo it right after.|“:name” goes with its :count services. You can undo it right after.', $category['serviceCount'], ['name' => $category['texts'][$viewModel->firstLanguage()]['shown']]) }}

          <x-slot:actions>
            <x-form method="delete" :action="$category['destroyUrl']" x-target="catalogue catalogue-footer" x-on:submit="confirmingRemoval = false">
              <x-button.danger>{{ __('Remove the category') }}</x-button.danger>
            </x-form>
          </x-slot>
        </x-confirm-dialog>
      @else
        <x-form method="delete" :action="$category['destroyUrl']" x-target="catalogue catalogue-footer">
          <button type="submit" class="{{ $iconButton }}" aria-label="{{ __('Remove this category') }}">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M4.5 4.5l.6 8.5h5.8l.6-8.5"></path></svg>
          </button>
        </x-form>
      @endif
    </div>
  </div>

  <div id="category-{{ $category['id'] }}-services" class="border-t border-hairline-soft" x-show="expanded">
    @foreach ($category['services'] as $service)
      @include('app.onboarding._service-row', ['service' => $service, 'viewModel' => $viewModel])
    @endforeach

    @if ($category['services'] === [])
      <p class="border-b border-hairline-soft px-5 py-4 text-sm text-muted">{{ __('No services in this category yet.') }}</p>
    @endif

    <x-form method="post" :action="$category['createServiceUrl']" x-target="catalogue catalogue-footer service-panel">
      <button type="submit" class="flex w-full cursor-pointer items-center gap-2 px-5 py-3.25 text-left text-sm font-semibold text-body transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
        {{ __('Add a service') }}
      </button>
    </x-form>
  </div>
</section>

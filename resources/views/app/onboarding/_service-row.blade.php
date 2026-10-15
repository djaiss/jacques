{{-- One service in the catalogue list, with its duration and its prices editable in place. --}}
{{--
  @var array<string, mixed> $service
  @var \App\ViewModels\Onboarding\ServicesViewModel $viewModel
--}}
@php
  $tips = collect($service['missing'])->map(fn (int $count, string $code): string => $count === 0 ? '' : trans_choice(':count text needs to be translated in :language.|:count texts need to be translated in :language.', $count, ['language' => config("jacques.content_languages.{$code}.label", $code)]))->all();
  $iconButton = 'flex size-8 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none';
  $fieldBox = 'flex h-8.5 items-center rounded-[10px] border border-hairline bg-input transition-colors hover:border-focus focus-within:border-focus focus-within:ring-2 focus-within:ring-focus-ring';
@endphp

<!-- service row -->
<div class="flex flex-wrap items-center gap-x-3.5 gap-y-2 border-b border-hairline-soft py-2.5 pr-4 pl-5 transition-colors hover:bg-hover" x-data="{ tips: @js($tips), showingTip: false }">
  <a
    href="{{ $service['editUrl'] }}"
    x-target="service-panel"
    class="flex min-w-0 flex-[1_1_200px] items-center gap-2.5 rounded-md py-1.5 text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
  >
    @foreach ($service['texts'] as $code => $text)
      <span x-show="language === @js($code)" @if (! $loop->first) x-cloak @endif class="truncate text-base font-semibold tracking-[-0.01em] {{ $text['isMissing'] ? 'text-muted' : 'text-ink' }}">{{ $text['shown'] }}</span>
    @endforeach

    <span
      x-show="language !== @js($viewModel->firstLanguage()) && tips[language] !== ''"
      x-cloak
      x-on:mouseenter="showingTip = true"
      x-on:mouseleave="showingTip = false"
      x-on:focusin="showingTip = true"
      x-on:focusout="showingTip = false"
      class="relative flex size-4.5 shrink-0 items-center justify-center"
    >
      <span class="size-2 rounded-full bg-error" aria-hidden="true"></span>
      <span class="sr-only" x-text="tips[language]"></span>
      <span x-show="showingTip" role="tooltip" class="absolute bottom-[calc(100%+8px)] left-1/2 z-20 w-max max-w-60 -translate-x-1/2 rounded-[10px] bg-menu px-2.75 py-2 text-xs leading-snug font-normal text-white shadow-2xl" x-text="tips[language]" aria-hidden="true"></span>
    </span>

    @if ($service['missingPriceLabel'] !== '')
      <span class="shrink-0 rounded-md bg-warning/14 px-2 py-0.5 text-xs font-bold tracking-wide text-warning uppercase">{{ $service['missingPriceLabel'] }}</span>
    @endif

    @if ($service['isMultiStep'])
      <span class="inline-flex shrink-0 items-center gap-1.25 rounded-md bg-accent-soft px-2 py-0.5 text-xs font-bold tracking-wide text-accent-ink uppercase">
        <svg width="10" height="10" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><rect x="0" y="4" width="3" height="4" rx="1"></rect><rect x="4.5" y="4" width="3" height="4" rx="1" opacity=".45"></rect><rect x="9" y="4" width="3" height="4" rx="1"></rect></svg>
        {{ $service['stepCountLabel'] }}
      </span>
    @endif
  </a>

  <div class="flex min-w-0 flex-wrap items-center gap-2">
    @if ($service['isMultiStep'])
      <p class="flex h-8.5 min-w-19.5 items-center justify-end gap-1 px-2.5 text-[15px] text-body tabular-nums" title="{{ __('Set by the steps of this service') }}">
        {{ $service['duration'] }}<span class="text-muted">{{ __('min') }}</span>
      </p>
    @else
      <x-form method="put" :action="$service['durationUrl']" x-target="catalogue catalogue-footer" x-on:change="$el.requestSubmit()">
        <label class="{{ $fieldBox }} min-w-19.5 cursor-text gap-1 px-2.5">
          <span class="sr-only">{{ __('Duration in minutes') }}</span>
          <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" x-on:beforeinput="if (/\D/.test($event.data ?? $event.dataTransfer?.getData('text') ?? '')) $event.preventDefault()" name="duration" value="{{ $service['duration'] }}" class="w-8.5 border-0 bg-transparent p-0 text-right text-[15px] text-ink tabular-nums focus:outline-none" />
          <span class="text-sm text-muted" aria-hidden="true">{{ __('min') }}</span>
        </label>
      </x-form>
    @endif

    <x-form method="put" :action="$service['pricesUrl']" x-target="catalogue catalogue-footer" x-on:change="$el.requestSubmit()">
      <div class="{{ $fieldBox }} overflow-hidden">
        @foreach ($service['prices'] as $price)
          <label title="{{ $price['name'] }}" class="flex h-full cursor-text items-center gap-0.75 px-2.25 {{ $loop->first ? '' : 'border-l border-hairline' }} {{ $price['isMissing'] ? 'bg-warning/10' : '' }}">
            <span class="text-[13px] {{ $price['isMissing'] ? 'text-warning' : 'text-muted' }}" aria-hidden="true">{{ $price['symbol'] }}</span>
            <input
              type="text"
              inputmode="decimal"
              name="prices[{{ $price['code'] }}]"
              value="{{ $price['amount'] }}"
              placeholder="–"
              aria-label="{{ __('Price in :currency', ['currency' => $price['name']]) }}"
              class="w-12 border-0 bg-transparent p-0 text-right text-[15px] text-ink tabular-nums placeholder:text-muted focus:outline-none"
            />
          </label>
        @endforeach
      </div>
    </x-form>

    <a href="{{ $service['editUrl'] }}" x-target="service-panel" class="{{ $iconButton }}" aria-label="{{ __('Edit this service') }}">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.5 2.5l3 3L6 13H3v-3z"></path></svg>
    </a>

    <x-form method="delete" :action="$service['destroyUrl']" x-target="catalogue catalogue-footer">
      <button type="submit" class="{{ $iconButton }}" aria-label="{{ __('Remove this service') }}">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line><line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line></svg>
      </button>
    </x-form>
  </div>

  @if ($errors->getBag("service_{$service['id']}")->any())
    <p class="w-full text-xs text-error" role="alert">{{ $errors->getBag("service_{$service['id']}")->first() }}</p>
  @endif
</div>

{{-- The languages of the salon, how much of the catalogue each has, and the one being edited. --}}
{{-- @var \App\ViewModels\Onboarding\ServicesViewModel $viewModel --}}
@php
  $languages = $viewModel->languages();
@endphp

<!-- catalogue languages -->
<section class="space-y-3.5 rounded-[18px] bg-canvas px-5 pt-4.5 pb-5 ring-[1.5px] ring-hairline" aria-labelledby="catalogue-languages-title">
  <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
    <div class="space-y-0.75">
      <h2 id="catalogue-languages-title" class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Languages') }}</h2>
      <p class="text-sm text-pretty text-body">{{ __('Clients see your catalog in the language they book in. Pick a language to edit its text.') }}</p>
    </div>
    <p class="text-xs text-muted">{{ $languages->summary() }}</p>
  </div>

  <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-2.5">
    @foreach ($languages->cards() as $card)
      <button
        type="button"
        x-on:click="language = @js($card['code'])"
        x-bind:aria-pressed="language === @js($card['code'])"
        class="flex cursor-pointer flex-col space-y-2.5 rounded-xl border-[1.5px] border-hairline bg-page px-3.5 py-3 aria-pressed:border-ink aria-pressed:bg-canvas text-left text-ink transition-colors hover:border-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
      >
        <span class="flex w-full items-center gap-2.5">
          <span class="block h-3.5 w-5.25 shrink-0 rounded-[3px] ring-1 ring-black/10" style="background: {{ $card['flag'] }}" aria-hidden="true"></span>
          <span class="flex-1 text-[15px] font-bold">{{ $card['label'] }}</span>
          @if ($card['isDefault'])
            <span class="rounded-md bg-hover px-2 py-0.5 text-xs font-bold tracking-wide text-muted uppercase ring-1 ring-hairline">{{ __('Default') }}</span>
          @endif
        </span>

        <span class="block h-1 w-full overflow-hidden rounded-xs bg-hairline" aria-hidden="true">
          <span class="block h-full rounded-xs transition-[width] {{ $card['hasNothingMissing'] ? 'bg-success' : 'bg-warning' }}" style="width: {{ $card['percent'] }}%"></span>
        </span>

        <span class="flex w-full justify-between gap-2 text-xs">
          <span class="text-muted tabular-nums">{{ $card['progress'] }}</span>
          <span class="flex items-center gap-1.25 font-semibold {{ $card['hasNothingMissing'] ? 'text-success' : 'text-warning' }}">
            @if ($card['isComplete'])
              <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7"></path></svg>
            @endif
            {{ $card['status'] }}
          </span>
        </span>
      </button>
    @endforeach
  </div>
</section>

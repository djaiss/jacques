{{-- A panel that slides in from the right of the window, over the screen it was opened from. --}}
{{--
  @var string $show
  @var string $close
  @var string|null $labelledby
  @var \Illuminate\View\ComponentSlot|null $header
--}}
@props([
  'show',
  'close',
  'labelledby' => null,
])

<div
  x-cloak
  x-show="{{ $show }}"
  x-transition.opacity.duration.110ms
  x-on:keydown.escape="{{ $close }}"
  x-on:click.self="{{ $close }}"
  x-effect="document.body.classList.toggle('overflow-hidden', !! ({{ $show }}))"
  :data-escape-guard="{{ $show }} ? '' : null"
  class="fixed inset-0 z-60 bg-black/25"
>
  <div
    x-show="{{ $show }}"
    x-transition:enter="transition duration-200 ease-out"
    x-transition:enter-start="translate-x-6 opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-trap.inert="{{ $show }}"
    role="dialog"
    aria-modal="true"
    @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
    {{ $attributes->class('absolute inset-y-3.5 right-3.5 flex w-[calc(100%-1.75rem)] max-w-110 flex-col overflow-hidden rounded-[20px] bg-canvas shadow-2xl ring-[1.5px] ring-hairline') }}
  >
    @isset($header)
      <div class="flex shrink-0 items-start gap-4 px-5.5 pt-6 pb-5 sm:px-7">
        <div class="min-w-0 flex-1">
          {{ $header }}
        </div>

        <button
          type="button"
          x-on:click="{{ $close }}"
          aria-label="{{ __('Close the panel') }}"
          class="flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
        >
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line>
            <line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line>
          </svg>
        </button>
      </div>
    @endisset

    <div class="min-h-0 flex-1 overflow-y-auto px-5.5 pb-7 sm:px-7">
      {{ $slot }}
    </div>
  </div>
</div>

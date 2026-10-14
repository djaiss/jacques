{{-- The dialog that asks before something is taken away for good. --}}
{{--
  @var string $show
  @var string $close
  @var string $labelledby
  @var string $title
  @var string $cancel
  @var \Illuminate\View\ComponentSlot $actions
--}}
@props([
  'show',
  'close',
  'labelledby',
  'title',
  'cancel',
])

<div
  x-cloak
  x-show="{{ $show }}"
  x-on:keydown.escape="{{ $close }}"
  x-on:click.self="{{ $close }}"
  :data-escape-guard="{{ $show }} ? '' : null"
  class="fixed inset-0 z-70 flex justify-center overflow-y-auto bg-black/35 px-4 py-14"
>
  <div
    x-trap.inert="{{ $show }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $labelledby }}"
    class="h-fit w-full max-w-115 rounded-[20px] bg-canvas px-6 pt-6 pb-7 shadow-2xl ring-[1.5px] ring-hairline sm:px-7"
  >
    <h2 id="{{ $labelledby }}" class="text-2xl font-bold tracking-tight text-ink">{{ $title }}</h2>

    <p class="mt-1.5 text-[15px] leading-relaxed text-pretty text-body">{{ $slot }}</p>

    <div class="mt-5.5 flex flex-wrap items-center gap-2.5">
      {{ $actions }}

      <x-button.secondary type="button" x-on:click="{{ $close }}">{{ $cancel }}</x-button.secondary>
    </div>
  </div>
</div>

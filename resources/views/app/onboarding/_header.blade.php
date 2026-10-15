{{-- The top of every onboarding step: where it sits, the way out, and the four steps. --}}
{{-- @var \App\ViewModels\Onboarding\StepViewModel $viewModel --}}
<x-breadcrumb
  :trail="[
    __('Getting started') => route('onboarding.index'),
    $viewModel->stepLabel() => null,
  ]"
/>

<div class="space-y-7">
  <!-- page title -->
  <div class="flex flex-wrap items-end justify-between gap-6">
    <div class="min-w-0 space-y-2">
      <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Onboarding · :step', ['step' => $viewModel->stepCounter()]) }}</p>
      <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] text-ink">{{ __('Welcome to :name', ['name' => $viewModel->salonName()]) }}</h1>
      <p class="text-lg leading-normal text-pretty text-body">{{ __('Four short steps, and your salon can take its first appointment.') }}</p>
    </div>

    <x-button.secondary :href="route('home.index')">{{ __('Finish later') }}</x-button.secondary>
  </div>

  <!-- step tracker -->
  <ol class="grid grid-cols-2 gap-y-5 rounded-[18px] bg-canvas px-5.5 py-5 ring-[1.5px] ring-hairline md:grid-cols-4" aria-label="{{ __('Onboarding steps') }}">
    @foreach ($viewModel->steps() as $step)
      @php
        $tileClasses = match (true) {
          $step['isDone'] => 'bg-ink text-canvas',
          $step['isCurrent'] => 'accent-tile',
          default => 'bg-page text-muted ring-1 ring-hairline',
        };
      @endphp

      <li class="flex min-w-0 flex-col space-y-3" @if ($step['isCurrent']) aria-current="step" @endif>
        <div class="flex items-center">
          <span class="{{ $tileClasses }} flex size-9.5 shrink-0 items-center justify-center rounded-xl text-[15px] font-bold tabular-nums" style="--tile-hue: {{ $step['hue'] }}" aria-hidden="true">
            @if ($step['isDone'])
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12.5l4.5 4.5L19 7.5" />
              </svg>
            @else
              {{ $step['number'] }}
            @endif
          </span>

          @if (! $loop->last)
            <span class="mx-2.5 hidden h-0.5 flex-1 rounded-full md:block {{ $step['isDone'] ? 'bg-ink' : 'bg-hairline' }}" aria-hidden="true"></span>
          @endif
        </div>

        <div class="min-w-0 space-y-0.75 pr-3">
          <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Step :number', ['number' => $step['number']]) }}</p>
          @if ($step['url'] !== null && ! $step['isCurrent'])
            <a href="{{ $step['url'] }}" class="block text-[17px] font-bold tracking-[-0.025em] text-ink transition-colors hover:text-brand">{{ $step['label'] }}</a>
          @else
            <p class="text-[17px] font-bold tracking-[-0.025em] {{ $step['isCurrent'] ? 'text-ink' : 'text-muted' }}">{{ $step['label'] }}</p>
          @endif
          @if ($step['isDone'])
            <span class="sr-only">{{ __('done') }}</span>
          @endif
        </div>
      </li>
    @endforeach
  </ol>
</div>

{{-- One service of the catalogue: how it does, what it is, and who does it in each salon. --}}
{{-- @var \App\ViewModels\Services\ServiceViewModel $viewModel --}}
@php
  $tones = [
    'success' => 'text-success',
    'error' => 'text-error',
    'warning' => 'text-warning',
    'neutral' => 'text-muted',
  ];
  $dots = [
    'success' => 'bg-success',
    'error' => 'bg-error',
    'warning' => 'bg-warning',
  ];
  $insights = $viewModel->insights();
@endphp

<x-top-bar-layout :title="$viewModel->name()">
  <x-breadcrumb :trail="$viewModel->breadcrumb()" />

  <!-- page title -->
  <div class="mb-10 flex flex-wrap items-end gap-4">
    <div class="min-w-70 flex-1 space-y-2">
      <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ $viewModel->category() }}</p>
      <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] wrap-anywhere text-ink">{{ $viewModel->name() }}</h1>
        @if ($viewModel->steps() !== '')
          <span class="accent-tile rounded-md px-2.25 py-1 text-xs font-bold tracking-[0.08em] uppercase" style="--tile-hue: 30">{{ $viewModel->steps() }}</span>
        @endif
      </div>
    </div>

    @if ($viewModel->editUrl())
      <x-button :href="$viewModel->editUrl()" class="h-10 max-sm:w-full">{{ __('Edit this service') }}</x-button>
    @endif
  </div>

  <div class="space-y-10">
    @if ($viewModel->isArchived())
      <!-- archived service -->
      <div class="flex flex-wrap items-center gap-4 rounded-[18px] border border-dashed border-hairline-strong bg-sunken px-6 py-5">
        <p class="min-w-60 flex-1 text-[15px] text-ink">{{ __('This service is archived. Nobody can book it, and it is not in the catalog.') }}</p>
        @if ($viewModel->restoreUrl())
          <x-form method="delete" :action="$viewModel->restoreUrl()">
            <x-button.secondary class="h-10">{{ __('Restore this service') }}</x-button.secondary>
          </x-form>
        @endif
      </div>
    @endif

    <!-- sample figures -->
    <section class="space-y-4" aria-labelledby="service-figures-title">
      <h2 id="service-figures-title" class="sr-only">{{ __('How it does') }}</h2>
      <x-notice>
        <p class="text-sm text-body">{{ __('Sample figures. Real ones arrive with appointments.') }}</p>
      </x-notice>

      <dl class="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
        @foreach ($viewModel->figures() as $figure)
          <div class="space-y-1 px-6 py-5.5 shadow-[-1px_0_0_var(--color-hairline-soft)]">
            <dt class="text-sm text-muted">{{ $figure['label'] }}</dt>
            <dd class="text-[32px] font-bold tracking-[-0.025em] text-ink tabular-nums">{{ $figure['value'] }}</dd>
            <dd class="text-[13px] text-muted">
              @if ($figure['trend'] !== '')
                <span class="font-semibold {{ $tones[$figure['tone']] }}">{{ $figure['trend'] }}</span>
              @endif
              {{ $figure['hint'] }}
            </dd>
          </div>
        @endforeach
      </dl>

      <div class="flex flex-wrap items-stretch gap-4">
        <!-- bookings per week -->
        <section class="min-w-0 flex-[2_1_520px] space-y-5 rounded-[18px] bg-canvas p-6 ring-[1.5px] ring-hairline" aria-labelledby="service-weeks-title">
          <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 id="service-weeks-title" class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Bookings per week') }}</h3>
            <span class="text-[13px] text-muted">{{ __('Last 12 weeks') }}</span>
          </div>
          <div role="img" aria-label="{{ $viewModel->weeksSummary() }}">
            <div class="grid h-40 grid-cols-12 items-end gap-1 border-b border-hairline sm:gap-2" aria-hidden="true">
              @foreach ($viewModel->weeks() as $week)
                <div class="grid h-full content-end justify-items-center gap-1.5">
                  <span class="text-xs text-muted tabular-nums">{{ $week['count'] }}</span>
                  <span class="block w-full max-w-9 rounded-t-md {{ $week['isCurrent'] ? 'bg-primary' : 'bg-hairline-strong' }}" style="height: {{ $week['height'] }}px"></span>
                </div>
              @endforeach
            </div>
            <div class="mt-2 grid grid-cols-12 gap-1 sm:gap-2" aria-hidden="true">
              @foreach ($viewModel->weeks() as $week)
                <span class="text-center text-xs text-muted">{{ $week['label'] }}</span>
              @endforeach
            </div>
          </div>
        </section>

        @if ($insights !== [])
          <!-- what stands out -->
          <section class="min-w-0 flex-[1_1_300px] space-y-4.5 rounded-[18px] bg-canvas p-6 ring-[1.5px] ring-hairline" aria-labelledby="service-insights-title">
            <h3 id="service-insights-title" class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('What stands out') }}</h3>
            <ul class="space-y-4.5">
              @foreach ($insights as $insight)
                <li class="grid grid-cols-[10px_minmax(0,1fr)] gap-3">
                  <span class="mt-1.5 size-2 rounded-full {{ $dots[$insight['tone']] }}" aria-hidden="true"></span>
                  <div class="space-y-0.75">
                    <p class="text-[15px] font-bold text-ink">{{ $insight['title'] }}</p>
                    <p class="text-sm leading-normal text-pretty text-body">{{ $insight['text'] }}</p>
                  </div>
                </li>
              @endforeach
            </ul>
          </section>
        @endif
      </div>
    </section>

    <!-- about -->
    <section class="space-y-3.5" aria-labelledby="service-about-title">
      <h2 id="service-about-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('About') }}</h2>
      <div class="grid overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="space-y-1.5 p-6">
          <p class="text-sm font-semibold text-ink">{{ __('Description') }}</p>
          @if ($viewModel->description() !== '')
            <p class="text-[15px] leading-relaxed text-pretty text-body">{{ $viewModel->description() }}</p>
          @else
            <p class="text-[15px] leading-relaxed text-muted">{{ __('No description yet. Clients only see the name when they book it.') }}</p>
          @endif
        </div>
        <dl class="border-hairline-soft max-md:border-t md:border-l">
          @foreach ($viewModel->facts() as $fact)
            <div class="flex justify-between gap-4 border-b border-hairline-soft px-6 py-3.5 text-[15px] last:border-b-0">
              <dt class="text-muted">{{ $fact['label'] }}</dt>
              <dd class="text-right font-semibold text-ink tabular-nums">{{ $fact['value'] }}</dd>
            </div>
          @endforeach
        </dl>
      </div>
    </section>

    <!-- who does it -->
    <section class="space-y-3.5" aria-labelledby="service-people-title">
      <div class="space-y-1">
        <h2 id="service-people-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Who does it') }}</h2>
        <p class="text-[15px] text-body">{{ __('Bookings over the last 30 days, per salon and per person.') }}</p>
      </div>

      @if ($viewModel->salons() === [])
        <p class="rounded-[18px] bg-canvas p-6 text-[15px] text-body ring-[1.5px] ring-hairline">{{ __('The company has no salon yet.') }}</p>
      @endif

      <div class="grid grid-cols-[repeat(auto-fill,minmax(min(300px,100%),1fr))] items-start gap-4">
        @foreach ($viewModel->salons() as $salon)
          <div class="overflow-hidden rounded-[14px] bg-canvas ring-[1.5px] ring-hairline">
            <div class="flex items-center gap-3 px-4 py-3.5">
              <span class="accent-tile size-3 shrink-0 rounded" style="--tile-hue: {{ $salon['hue'] }}" aria-hidden="true"></span>
              <h3 class="text-[15px] font-bold text-ink">{{ $salon['name'] }}</h3>
              @if ($salon['isHeadquarters'])
                <span class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Head office') }}</span>
              @endif
              <span class="flex-1"></span>
              <span class="text-[13px] text-muted tabular-nums">{{ $salon['total'] }}</span>
            </div>

            @if ($salon['isOff'])
              <p class="border-t border-hairline-soft px-4 pt-3 pb-3.5 text-sm text-muted">{{ __('Not offered here.') }}</p>
            @elseif ($salon['isEmpty'])
              <p class="border-t border-hairline-soft px-4 pt-3 pb-3.5 text-sm text-warning">{{ __('Offered here, but nobody does it yet, so it cannot be booked.') }}</p>
            @else
              <ul class="grid gap-0.5 border-t border-hairline-soft px-2 pt-2 pb-2.5">
                @foreach ($salon['people'] as $person)
                  <li class="flex items-center gap-2.5 px-2 py-1.5">
                    <x-avatar :staff="$person['staff']" :name="$person['name']" :size="26" />
                    <span class="flex-1 text-sm text-ink">{{ $person['name'] }}</span>
                    <span class="text-[13px] text-muted tabular-nums">{{ $person['bookings'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        @endforeach
      </div>
    </section>
  </div>
</x-top-bar-layout>

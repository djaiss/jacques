{{-- The day shown on the dashboard: one column per checked hairdresser, from opening to closing. --}}
{{-- @var \App\ViewModels\Dashboard\DashboardViewModel $viewModel --}}
@php
  $selectClasses = 'block w-full cursor-pointer appearance-none rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2 text-sm text-ink transition-colors duration-150 hover:border-ink hover:bg-hover focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none';
  $day = $viewModel->day();
  $columns = $day->columns();
@endphp

<!-- day agenda -->
<section aria-labelledby="day-title" class="flex min-w-0 flex-1 flex-col overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
  <!-- day header -->
  <div class="flex flex-wrap items-center gap-3 border-b border-hairline px-4.5 py-3.5">
    <x-button.secondary :href="$viewModel->todayUrl()">{{ __('Today') }}</x-button.secondary>

    <div class="flex gap-1.5">
      <x-button.secondary :href="$viewModel->previousDayUrl()" aria-label="{{ __('Previous day') }}" class="px-2.75">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M10 3L5 8l5 5"></path>
        </svg>
      </x-button.secondary>

      <x-button.secondary :href="$viewModel->nextDayUrl()" aria-label="{{ __('Next day') }}" class="px-2.75">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6 3l5 5-5 5"></path>
        </svg>
      </x-button.secondary>
    </div>

    <div class="ml-1.5 flex flex-col">
      <h1 id="day-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink first-letter:uppercase">{{ $viewModel->title() }}</h1>
      <p class="text-xs text-muted" @unless ($viewModel->isClosed()) x-text="summary()" @endunless>{{ $viewModel->summary() }}</p>
    </div>

    @unless ($viewModel->isClosed())
      <div class="flex items-center gap-2.5 text-sm text-body sm:ml-auto">
        <label for="slot-size">{{ __('Slots of') }}</label>
        <div class="w-27.5">
          <select id="slot-size" x-model.number="slot" class="{{ $selectClasses }}">
            @foreach ([15, 20, 30] as $minutes)
              <option value="{{ $minutes }}" @selected($minutes === 20)>{{ __(':minutes min', ['minutes' => $minutes]) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    @endunless
  </div>

  @if ($viewModel->isClosed())
    <!-- closed salon -->
    <div class="flex flex-col items-center gap-1.5 px-8 py-20 text-center">
      <p class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('The salon is closed that day') }}</p>
      <p class="max-w-105 text-sm text-pretty text-body">{{ __('Nobody works on Sundays and no appointment can be booked. Pick another day in the calendar.') }}</p>
    </div>
  @else
    <!-- no hairdresser -->
    <div x-cloak x-show="visible.length === 0" class="flex flex-col items-center gap-1.5 px-8 py-20 text-center">
      <p class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('No hairdresser is shown') }}</p>
      <p class="max-w-105 text-sm text-pretty text-body">{{ __('Check at least one hairdresser in the list to see their day.') }}</p>
    </div>

    <!-- agenda grid -->
    <div x-show="visible.length > 0" class="max-h-[calc(100vh-186px)] overflow-auto">
      <div class="min-w-fit">
        <!-- column headers -->
        <div class="sticky top-0 z-4 flex border-b border-hairline bg-canvas">
          <div class="w-16 shrink-0"></div>

          @foreach ($columns as $column)
            <div x-show="isVisible('{{ $column['id'] }}')" @unless ($column['visible']) x-cloak @endunless class="flex min-w-47.5 flex-1 items-center gap-2.5 border-l border-hairline-soft px-3.5 py-3">
              <span style="--tile-hue: {{ $column['hue'] }}">
                <x-avatar-initials :name="$column['name']" :size="34" tone="accent" />
              </span>

              <div class="min-w-0">
                <h2 class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ $column['name'] }}</h2>
                <p class="text-xs text-muted" @unless ($column['absent']) x-text="countLabel('{{ $column['id'] }}')" @endunless>{{ $column['countLabel'] }}</p>
              </div>
            </div>
          @endforeach
        </div>

        <!-- working hours -->
        <div class="flex border-b border-hairline bg-sunken">
          <div class="w-16 shrink-0 pt-2.5 pr-2 text-right text-xs text-muted">{{ __('Day') }}</div>

          @foreach ($columns as $column)
            <div x-show="isVisible('{{ $column['id'] }}')" @unless ($column['visible']) x-cloak @endunless class="flex min-h-9 min-w-47.5 flex-1 flex-wrap items-center gap-1.5 border-l border-hairline-soft px-2.5 py-2">
              @if ($column['absent'])
                <span class="rounded-lg bg-accent-soft px-2 py-1 text-xs font-bold tracking-[0.08em] text-accent-ink uppercase">{{ __('On leave') }}</span>
              @else
                <span class="rounded-lg bg-canvas px-2 py-1 text-xs font-bold text-ink">{{ $column['hoursLabel'] }}</span>
                <span class="px-2 py-1 text-xs text-muted">{{ $column['lunchLabel'] }}</span>
              @endif
            </div>
          @endforeach
        </div>

        <!-- time grid -->
        <div class="relative flex">
          <div class="relative w-16 shrink-0" style="height: {{ $day->height() }}px" aria-hidden="true">
            @foreach ($day->hours() as $hour)
              <span class="absolute right-2.5 -translate-y-1/2 bg-canvas py-0.5 text-xs text-muted tabular-nums" style="top: {{ $hour['top'] }}px">{{ $hour['label'] }}</span>
            @endforeach
          </div>

          @foreach ($columns as $column)
            <div
              x-show="isVisible('{{ $column['id'] }}')"
              @unless ($column['visible']) x-cloak @endunless
              class="agenda-lines relative min-w-47.5 flex-1 border-l border-hairline-soft"
              style="height: {{ $day->height() }}px; --hour-height: {{ $day->hourHeight() }}px; --slot-height: 30px; --tile-hue: {{ $column['hue'] }}"
              x-bind:style="{ '--slot-height': `${slot * pixelsPerMinute}px` }"
            >
              @unless ($column['absent'])
                <template x-for="free in slots('{{ $column['id'] }}')" :key="free.start">
                  <button
                    type="button"
                    x-on:click="book('{{ $column['id'] }}', free.start)"
                    x-bind:aria-label="free.label"
                    x-bind:style="{ top: `${free.top}px`, height: `${free.height}px` }"
                    class="absolute inset-x-0 cursor-copy rounded-md transition-colors duration-150 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset"
                  ></button>
                </template>
              @endunless

              @foreach ($column['offBlocks'] as $off)
                <div class="agenda-off absolute inset-x-0 px-2.5 py-1.5 text-xs text-muted" style="top: {{ $off['top'] }}px; height: {{ $off['height'] }}px">{{ $off['label'] }}</div>
              @endforeach

              @foreach ($column['appointments'] as $appointment)
                <button
                  type="button"
                  x-show="! removed.includes('{{ $appointment['id'] }}')"
                  x-on:click="view({{ Js::from($appointment) }})"
                  @class([
                    'appointment-tile absolute right-1.5 left-1 flex cursor-pointer gap-x-2 overflow-hidden rounded-[10px] px-2.25 py-1.25 text-left transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none',
                    'flex-row items-baseline' => $appointment['compact'],
                    'flex-col' => ! $appointment['compact'],
                  ])
                  style="top: {{ $appointment['top'] }}px; height: {{ $appointment['height'] }}px"
                >
                  <span class="text-xs whitespace-nowrap text-body tabular-nums">{{ $appointment['time'] }}</span>
                  <span class="truncate text-sm font-bold text-ink">{{ $appointment['client'] }}</span>
                  <span class="truncate text-xs text-body">{{ $appointment['service'] }}</span>
                </button>
              @endforeach

              <template x-for="appointment in addedOf('{{ $column['id'] }}')" :key="appointment.id">
                <button
                  type="button"
                  x-on:click="view(appointment)"
                  x-bind:class="appointment.compact ? 'flex-row items-baseline' : 'flex-col'"
                  x-bind:style="{ top: `${appointment.top}px`, height: `${appointment.height}px` }"
                  class="appointment-tile absolute right-1.5 left-1 flex cursor-pointer gap-x-2 overflow-hidden rounded-[10px] px-2.25 py-1.25 text-left transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
                >
                  <span class="text-xs whitespace-nowrap text-body tabular-nums" x-text="appointment.time"></span>
                  <span class="truncate text-sm font-bold text-ink" x-text="appointment.client"></span>
                  <span class="truncate text-xs text-body" x-text="appointment.service"></span>
                </button>
              </template>
            </div>
          @endforeach

          <!-- current time -->
          <div
            @if (! $viewModel->showsCurrentTime()) x-cloak @endif
            x-show="showsNow"
            x-bind:style="{ top: `${nowTop}px` }"
            class="pointer-events-none absolute inset-x-0 z-5 h-0 border-t-2 border-error"
            style="top: {{ $viewModel->currentTimeTop() }}px"
            aria-hidden="true"
          >
            <span class="absolute -top-2.75 left-1 rounded-sm bg-error px-1.5 py-0.5 text-xs leading-4 font-bold text-white tabular-nums" x-text="nowLabel">{{ $viewModel->currentTimeLabel() }}</span>
          </div>
        </div>
      </div>
    </div>
  @endif
</section>

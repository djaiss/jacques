{{-- The month calendar of the dashboard, to pick the day the agenda shows. --}}
{{-- @var \App\ViewModels\Dashboard\MonthCalendar $calendar --}}
<!-- month calendar -->
<nav id="calendar" aria-labelledby="calendar-title" class="rounded-[18px] bg-canvas px-4 pt-4.5 pb-3.5 ring-[1.5px] ring-hairline">
  <div class="mb-3 flex items-center justify-between px-1">
    <h2 id="calendar-title" class="text-[17px] font-bold tracking-[-0.025em] text-ink first-letter:uppercase">{{ $calendar->label() }}</h2>

    <div class="flex gap-1">
      <a
        href="{{ $calendar->previousUrl() }}"
        x-target="calendar"
        aria-label="{{ __('Previous month') }}"
        class="flex size-7.5 items-center justify-center rounded-lg text-ink transition-colors duration-150 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
      >
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M10 3L5 8l5 5"></path>
        </svg>
      </a>

      <a
        href="{{ $calendar->nextUrl() }}"
        x-target="calendar"
        aria-label="{{ __('Next month') }}"
        class="flex size-7.5 items-center justify-center rounded-lg text-ink transition-colors duration-150 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
      >
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6 3l5 5-5 5"></path>
        </svg>
      </a>
    </div>
  </div>

  <div class="grid grid-cols-7 gap-0.5 pb-1.5 text-center text-xs font-bold text-muted">
    @foreach ($calendar->weekdays() as $weekday)
      <abbr title="{{ $weekday['name'] }}" class="no-underline">{{ $weekday['initial'] }}</abbr>
    @endforeach
  </div>

  <div class="grid grid-cols-7 gap-0.5">
    @foreach ($calendar->weeks() as $week)
      @foreach ($week as $day)
        <a
          href="{{ $day['url'] }}"
          aria-label="{{ $day['label'] }}"
          @if ($day['isSelected']) aria-current="page" @endif
          @class([
            'flex h-8.5 items-center justify-center rounded-[10px] text-sm tabular-nums transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none',
            'bg-primary font-bold text-on-primary' => $day['isSelected'],
            'hover:bg-hover' => ! $day['isSelected'],
            'text-ink' => ! $day['isSelected'] && $day['inMonth'] && ! $day['isSunday'],
            'text-muted-soft' => ! $day['isSelected'] && (! $day['inMonth'] || $day['isSunday']),
            'font-bold ring-[1.5px] ring-ink ring-inset' => $day['isToday'] && ! $day['isSelected'],
          ])
        >{{ $day['number'] }}</a>
      @endforeach
    @endforeach
  </div>
</nav>

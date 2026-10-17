{{-- The hours each bookable team member works: the opening hours, or hours of their own. --}}
{{-- @var \App\ViewModels\Onboarding\AvailabilityViewModel $viewModel --}}
<!-- working hours of the team -->
<section class="space-y-4" aria-labelledby="team-hours-title">
  <div class="max-w-160 space-y-1">
    <h2 id="team-hours-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Working hours of the team') }}</h2>
    <p class="text-sm leading-normal text-pretty text-body">{{ __('Clients can book somebody only during their working hours. Somebody who takes a lunch break can work in two parts of the day.') }}</p>
  </div>

  <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
    <!-- everybody on the opening hours -->
    <div class="space-y-2 border-b border-hairline-soft p-4.5">
      <label for="use-opening-hours" class="flex cursor-pointer items-start gap-2.5">
        <input type="checkbox" id="use-opening-hours" class="peer sr-only" x-model="useOpeningHours" />
        <span aria-hidden="true" class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-control bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring">
          <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path></svg>
        </span>
        <span class="text-[15px] font-semibold text-ink">{{ __('Use salon opening hours for all team members') }}</span>
      </label>
      <p class="pl-6.5 text-sm leading-normal text-pretty text-muted" x-text="useOpeningHours ? @js($viewModel->useOpeningHoursHelp()) : @js($viewModel->customHoursHelp())"></p>
    </div>

    <ul id="team-hours" x-merge="replace">
      @foreach ($viewModel->teamRows() as $row)
        <!-- team member hours -->
        <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-5 gap-y-2.5 border-b border-hairline-soft px-4.5 py-3.5 last:border-b-0 max-sm:grid-cols-1">
          <span class="flex min-w-0 items-center gap-3.5">
            <span style="--tile-hue: {{ $row['hue'] }}">
              <x-avatar-initials :name="$row['name']" :size="42" tone="accent" />
            </span>
            <span class="flex min-w-0 flex-col gap-0.75">
              <span class="truncate text-[17px] font-bold tracking-[-0.025em] text-ink">{{ $row['name'] }}</span>
              @if ($row['jobTitle'] !== null)
                <span class="truncate text-sm text-muted">{{ $row['jobTitle'] }}</span>
              @endif
            </span>
          </span>

          <span class="flex flex-wrap items-center justify-end gap-4.5 max-sm:justify-start">
            <span class="flex min-w-42.5 flex-col gap-0.5">
              @if ($row['follows'])
                <span class="text-sm font-semibold text-ink">{{ __('Salon opening hours') }}</span>
                <span class="text-xs text-muted tabular-nums" x-text="summary(days)"></span>
              @else
                <span class="text-sm font-semibold text-ink" x-show="useOpeningHours">{{ __('Salon opening hours') }}</span>
                <span class="text-sm font-semibold text-ink" x-show="! useOpeningHours" x-cloak>{{ __('Custom hours') }}</span>
                <span class="text-xs text-muted tabular-nums" x-show="useOpeningHours" x-text="summary(days)"></span>
                <span class="text-xs text-muted tabular-nums" x-show="! useOpeningHours" x-cloak>{{ $row['summary'] }}</span>
              @endif
            </span>

            <x-button.secondary :href="$row['editUrl']" x-target="hours-panel" x-show="! useOpeningHours" x-cloak class="py-2">
              {{ $row['follows'] ? __('Customize') : __('Edit hours') }}
              <span class="sr-only">{{ $row['name'] }}</span>
            </x-button.secondary>
          </span>
        </li>
      @endforeach
    </ul>
  </div>
</section>

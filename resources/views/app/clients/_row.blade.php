{{-- One client of the list: how to reach them, where and when they last came, what is booked. --}}
{{--
  @var array<string, mixed> $row
  @var string $columns
--}}
<li class="grid items-center gap-x-4 gap-y-2 border-t border-hairline-soft px-6 py-3.5 first:border-t-0 {{ $columns }}">
  <!-- name and phone -->
  <div class="flex min-w-0 items-center gap-3.5">
    <x-avatar-initials :name="$row['name']" :size="38" tone="accent" />
    <div class="grid min-w-0 gap-0.75">
      <span class="truncate text-[17px] font-bold tracking-[-0.025em] text-ink" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
      @if ($row['phone'] !== null)
        <span class="text-sm text-body tabular-nums">{{ $row['phone'] }}</span>
      @endif
    </div>
  </div>

  <span class="text-sm text-body">
    @if ($row['salon'] !== null)
      <span class="text-muted md:sr-only">{{ __('Salon:') }} </span>{{ $row['salon'] }}
    @endif
  </span>

  <!-- last visit -->
  <div class="grid gap-0.5">
    <span class="text-sm text-ink"><span class="text-muted md:sr-only">{{ __('Last visit:') }} </span>{{ $row['lastVisit'] ?? __('No visit yet') }}</span>
    @if ($row['lastVisitAgo'] !== null)
      <span class="text-xs text-muted">{{ $row['lastVisitAgo'] }}</span>
    @endif
  </div>

  <!-- next appointment -->
  <div class="flex items-center gap-2 text-sm text-body">
    <span @class(['size-2 shrink-0 rounded-full', 'bg-success' => $row['nextAppointment'] !== null, 'bg-muted-soft' => $row['nextAppointment'] === null]) aria-hidden="true"></span>
    <span><span class="text-muted md:sr-only">{{ __('Next appointment:') }} </span>{{ $row['nextAppointment'] ?? __('None booked') }}</span>
  </div>

  <span class="text-sm tabular-nums md:text-right"><span class="text-muted md:sr-only">{{ __('Visits:') }} </span>{{ $row['visitCount'] }}</span>
</li>

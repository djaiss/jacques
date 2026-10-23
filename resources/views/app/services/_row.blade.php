{{-- One service of the catalogue list: what it costs, how long it lasts, where and by whom. --}}
{{--
  @var array<string, mixed> $row
  @var string $columns
--}}
<li class="border-t border-hairline-soft">
  <a
    href="{{ $row['url'] }}"
    class="grid min-h-16 items-center gap-x-4 gap-y-2 bg-canvas px-6 py-3 transition-colors hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset sm:grid-cols-2 {{ $columns }}"
  >
    <!-- name, duration and steps -->
    <span class="flex min-w-0 flex-wrap items-center gap-x-2.5 gap-y-1.5 sm:col-span-2 lg:col-span-1">
      <span class="text-[17px] font-bold tracking-[-0.025em] {{ $row['isDimmed'] || $row['isUntitled'] ? 'text-muted-soft' : 'text-ink' }}">{{ $row['name'] }}</span>
      <span class="basis-full text-sm text-body xl:hidden">{{ $row['duration'] }}</span>
      @if ($row['steps'] !== '')
        <span class="accent-tile rounded-md px-2 py-0.75 text-xs font-bold tracking-[0.08em] whitespace-nowrap uppercase" style="--tile-hue: 30">{{ $row['steps'] }}</span>
      @endif
    </span>

    <span class="hidden text-right text-[15px] text-body tabular-nums xl:block">{{ $row['duration'] }}</span>

    <!-- prices -->
    <span class="flex flex-wrap gap-3 text-[15px] tabular-nums lg:justify-end">
      @foreach ($row['prices'] as $price)
        <span class="whitespace-nowrap">
          <span class="text-muted">{{ $price['symbol'] }}</span>
          @if ($price['amount'] === '')
            <span class="font-semibold text-warning">{{ __('No price') }}</span>
          @else
            <span class="font-semibold text-ink">{{ $price['amount'] }}</span>
          @endif
        </span>
      @endforeach
    </span>

    <!-- salons -->
    <span class="min-w-0 text-[15px] {{ $row['salons']['isWarning'] ? 'text-warning' : 'text-body' }}" @if ($row['salons']['names'] !== '') title="{{ $row['salons']['names'] }}" @endif>{{ $row['salons']['label'] }}</span>

    <!-- people -->
    <span class="flex min-w-0 items-center">
      @if ($row['notOfferedHere'] !== '')
        <span class="text-[15px] text-muted-soft">{{ $row['notOfferedHere'] }}</span>
      @elseif ($row['nobody'])
        <span class="text-[15px] text-warning">{{ __('Nobody does it yet') }}</span>
      @elseif ($row['people'] !== [])
        <span class="flex items-center pl-1">
          @foreach ($row['people'] as $person)
            <x-avatar :staff="$person['staff']" :name="$person['name']" :size="30" class="-ml-1 border-2 border-canvas" title="{{ $person['name'] }}" />
          @endforeach
          <span class="sr-only">{{ $row['peopleNames'] }}</span>
          @if ($row['more'] > 0)
            <span class="ml-2 text-[13px] font-semibold whitespace-nowrap text-muted">{{ trans_choice('[0,*]+:count', $row['more']) }}</span>
          @endif
        </span>
      @endif
    </span>

    <span class="hidden size-2 rotate-[-45deg] border-r-2 border-b-2 border-muted lg:block" aria-hidden="true"></span>
  </a>
</li>

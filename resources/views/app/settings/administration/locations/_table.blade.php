{{-- Every location of the list, one to a row. --}}
{{-- @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel --}}
@php
  $columns = 'grid-cols-[38px_minmax(0,1fr)_10px] gap-x-4.5 gap-y-1.5 md:grid-cols-[38px_minmax(0,1fr)_auto_10px] md:gap-y-0';
@endphp

<div id="locations-table" class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline transition-opacity [&[aria-busy]]:opacity-60">
  @forelse ($viewModel->rows() as $row)
    @php
      $hue = match (true) {
        $row['isArchived'] => 200,
        $row['isHeadquarters'] => 30,
        default => 80,
      };
    @endphp

    <button
      type="button"
      x-on:click="edit({{ $row['id'] }})"
      :class="open === {{ $row['id'] }} && 'bg-hover'"
      @class([
        'grid w-full cursor-pointer items-center border-b border-hairline-soft px-5.5 py-4 text-left transition-colors last:border-b-0 hover:bg-hover',
        $columns,
        'bg-sunken' => $row['isArchived'],
      ])
    >
      <span class="accent-tile grid size-9.5 place-items-center rounded-xl" style="--tile-hue: {{ $hue }}">
        <x-settings-icon name="locations" />
      </span>

      <span class="min-w-0">
        <span class="mb-0.75 flex flex-wrap items-center gap-x-2.25 gap-y-1">
          <span class="text-[17px] font-bold tracking-tight text-ink">{{ $row['name'] }}</span>

          @if ($row['badge'])
            <span class="rounded-md px-2 py-0.5 text-xs font-bold tracking-wide uppercase {{ $row['isArchived'] ? 'bg-hover text-muted ring-1 ring-hairline' : 'bg-accent-soft text-accent-ink' }}">
              {{ $row['badge'] }}
            </span>
          @endif
        </span>

        <span class="block truncate text-[15px] text-body">{{ $row['place'] }}</span>
      </span>

      <span class="truncate text-[15px] text-muted max-md:col-start-2 max-md:row-start-2 md:text-right">{{ $row['timezone'] }}</span>

      <span class="block size-2 -rotate-45 justify-self-end border-r-2 border-b-2 border-muted-soft max-md:col-start-3 max-md:row-start-1" aria-hidden="true"></span>
    </button>
  @empty
    @if ($viewModel->companyHasNoLocation())
      <x-empty-state :title="__('This company has no location')">
        <x-slot:icon>
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
            <path d="M8 14s4.4-4 4.4-7A4.4 4.4 0 0 0 3.6 7c0 3 4.4 7 4.4 7Z"></path>
            <circle cx="8" cy="6.8" r="1.7"></circle>
          </svg>
        </x-slot:icon>

        {{ __('A company that works entirely remotely needs none. Add one as soon as it rents a desk somewhere, and people can be sent to it.') }}
      </x-empty-state>
    @else
      <x-empty-state :title="__('No location matches this search')">
        <x-slot:icon>
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
            <circle cx="7.2" cy="7.2" r="4.2"></circle>
            <line x1="10.4" y1="10.4" x2="13.4" y2="13.4"></line>
          </svg>
        </x-slot:icon>

        {{ __('Archived locations are kept off this list. Switch to the archived ones, or to all of them, if that is what you are looking for.') }}
      </x-empty-state>
    @endif
  @endforelse
</div>

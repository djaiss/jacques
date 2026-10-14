{{-- Every role of the company, one to a row. --}}
{{-- @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel --}}
@php
  $columns = 'grid-cols-[38px_minmax(0,1fr)_10px] gap-x-4.5 gap-y-2 md:grid-cols-[38px_minmax(0,1fr)_auto_auto_10px] md:gap-y-0';
@endphp

<div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
  @forelse ($viewModel->rows() as $row)
    <a
      href="{{ $row['url'] }}"
      data-turbo="true"
      class="grid w-full cursor-pointer items-center border-b border-hairline-soft px-5.5 py-4 text-left transition-colors last:border-b-0 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset {{ $columns }}"
    >
      <span class="accent-tile grid size-9.5 place-items-center rounded-xl" style="--tile-hue: {{ $row['hue'] }}">
        <x-settings-icon name="roles" />
      </span>

      <span class="min-w-0">
        <span class="mb-0.75 flex flex-wrap items-center gap-x-2.25 gap-y-1">
          <span class="text-[17px] font-bold tracking-tight text-ink">{{ $row['name'] }}</span>

          @foreach ($row['badges'] as $badge)
            <span class="rounded-md px-2 py-0.5 text-xs font-bold tracking-wide uppercase {{ $badge['tone'] === 'accent' ? 'bg-accent-soft text-accent-ink' : 'bg-hover text-muted ring-1 ring-hairline' }}">
              {{ $badge['label'] }}
            </span>
          @endforeach
        </span>

        <span class="block text-[15px] text-pretty text-body">{{ $row['summary'] }}</span>
      </span>

      <span class="text-[15px] whitespace-nowrap text-muted max-md:col-start-2 max-md:row-start-2">{{ $row['permissions'] }}</span>

      <span class="text-[15px] font-semibold whitespace-nowrap text-body max-md:col-start-2 max-md:row-start-3 md:text-right">{{ $row['holders'] }}</span>

      <span class="block size-2 -rotate-45 justify-self-end border-r-2 border-b-2 border-muted-soft max-md:col-start-3 max-md:row-start-1" aria-hidden="true"></span>
    </a>
  @empty
    <x-empty-state :title="__('This company has no roles left')">
      <x-slot:icon>
        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
          <path d="M8 2.2 13.2 4v4c0 3-2.2 5-5.2 5.8C5 13 2.8 11 2.8 8V4L8 2.2Z"></path>
        </svg>
      </x-slot:icon>

      {{ __('Nobody can be given anything until there is a role to give. Make one to start handing out permissions again.') }}
    </x-empty-state>
  @endforelse
</div>

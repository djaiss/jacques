{{-- The three counts above the list. --}}
{{-- @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel --}}
<div id="locations-statistics" class="mb-6 grid gap-3 transition-opacity sm:grid-cols-3 [&[aria-busy]]:opacity-60">
  @foreach ($viewModel->stats() as $stat)
    <div class="flex items-center gap-3.5 rounded-[18px] bg-canvas px-4.5 py-4 ring-[1.5px] ring-hairline">
      <span class="accent-tile grid size-9.5 shrink-0 place-items-center rounded-xl" style="--tile-hue: {{ $stat['hue'] }}">
        <x-settings-icon :name="$stat['icon']" />
      </span>

      <div>
        <p class="text-[26px] leading-none font-bold tracking-tight text-ink tabular-nums">{{ $stat['value'] }}</p>

        <p class="mt-1.5 text-sm text-muted">{{ $stat['label'] }}</p>
      </div>
    </div>
  @endforeach
</div>

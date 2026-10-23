{{-- The hairdressers of the dashboard, each checked to show their column in the day. --}}
{{-- @var list<array{id: string, name: string, hue: int, visible: bool}> $hairdressers --}}
<!-- hairdressers -->
<section aria-labelledby="hairdressers-title" class="flex flex-col gap-3 rounded-[18px] bg-canvas px-4.5 pt-4.5 pb-4 ring-[1.5px] ring-hairline">
  <div>
    <h2 id="hairdressers-title" class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Hairdressers') }}</h2>
    <p class="mt-0.5 text-xs text-muted">{{ __('Check those you want to see in the day.') }}</p>
  </div>

  <x-checkbox id="hairdresser-all" x-bind:checked="allVisible()" x-on:change="toggleAll()">
    <span class="font-bold text-ink">{{ __('All hairdressers') }}</span>
  </x-checkbox>

  <div class="h-px bg-hairline-soft"></div>

  @foreach ($hairdressers as $hairdresser)
    <x-checkbox id="hairdresser-{{ $hairdresser['id'] }}" :value="$hairdresser['id']" :checked="$hairdresser['visible']" x-model="visible">
      <span class="flex items-center gap-2 text-ink">
        <span style="--tile-hue: {{ $hairdresser['hue'] }}">
          <x-avatar-initials :name="$hairdresser['name']" :size="22" tone="accent" />
        </span>
        {{ $hairdresser['name'] }}
      </span>
    </x-checkbox>
  @endforeach
</section>

{{-- One setting on the hub: what it is, what it holds right now, and the way into it. --}}
{{-- @var array{title: string, description: string, value: string, url: string, hue: int, icon: string} $row --}}
<a
  href="{{ $row['url'] }}"
  data-turbo="true"
  class="grid grid-cols-[38px_minmax(0,1fr)_10px] items-center gap-4.5 border-b border-hairline-soft px-5.5 py-4.5 transition-colors last:border-b-0 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset sm:grid-cols-[38px_minmax(0,1fr)_auto_10px]"
>
  <span class="accent-tile grid size-9.5 place-items-center rounded-xl" style="--tile-hue: {{ $row['hue'] }}">
    <x-settings-icon :name="$row['icon']" />
  </span>

  <span class="min-w-0">
    <span class="mb-0.75 block text-[17px] font-bold tracking-tight text-ink">{{ $row['title'] }}</span>
    <span class="block text-[15px] leading-relaxed text-body">{{ $row['description'] }}</span>
    <span class="mt-1 block text-[15px] font-semibold wrap-anywhere text-muted sm:hidden">{{ $row['value'] }}</span>
  </span>

  <span class="hidden text-right text-[15px] font-semibold wrap-anywhere text-muted sm:block" aria-hidden="true">{{ $row['value'] }}</span>

  <span class="block size-2 -rotate-45 justify-self-end border-r-2 border-b-2 border-muted-soft" aria-hidden="true"></span>
</a>

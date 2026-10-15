{{-- One person of the team: who they are, what they do and whether they will have an account. --}}
{{-- @var array<string, mixed> $row --}}
<!-- team member row -->
<li class="border-b border-hairline-soft">
  <a
    href="{{ $row['editUrl'] }}"
    x-target="staff-panel"
    class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-5 gap-y-2.5 px-4.5 py-4 text-ink transition-colors hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset max-sm:grid-cols-1"
  >
    <span class="flex min-w-0 items-center gap-3.5">
      <span style="--tile-hue: {{ $row['hue'] }}">
        <x-avatar-initials :name="$row['name']" :size="42" tone="accent" />
      </span>

      <span class="flex min-w-0 flex-col gap-0.75">
        <span class="flex min-w-0 items-center gap-2">
          <span class="truncate text-[17px] font-bold tracking-[-0.025em]">{{ $row['name'] }}</span>
          @if ($row['isOwnRecord'])
            <span class="rounded-md bg-hover px-2 py-0.5 text-xs font-bold tracking-wide text-muted uppercase ring-1 ring-hairline">{{ __('You') }}</span>
          @endif
        </span>
        <span class="truncate text-sm text-muted">{{ $row['subtitle'] }}</span>
      </span>
    </span>

    <span class="flex flex-wrap items-center justify-end gap-4.5 max-sm:justify-start">
      <span class="flex min-w-30 flex-col gap-0.5">
        <span class="text-xs text-muted">{{ __('Services') }}</span>
        <span class="flex items-center gap-1.5 text-sm font-semibold {{ $row['hasNoService'] ? 'text-warning' : 'text-ink' }}">
          @if ($row['hasNoService'])
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2.5l6 11H2z"></path><path d="M8 7v3M8 12h.01"></path></svg>
          @endif
          {{ $row['services'] }}
        </span>
      </span>

      <span class="flex min-w-37.5 flex-col gap-0.5">
        <span class="text-xs text-muted">{{ __('Account') }}</span>
        <span class="flex items-center gap-1.5 text-sm font-semibold {{ $row['hasInvitation'] ? 'text-success' : ($row['isOwnRecord'] ? 'text-ink' : 'text-muted') }}">
          @if ($row['hasInvitation'])
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3.5" width="12" height="9" rx="1.5"></rect><path d="M2.5 4.5L8 9l5.5-4.5"></path></svg>
          @endif
          {{ $row['account'] }}
        </span>
      </span>

      <span class="flex size-8 items-center justify-center rounded-lg text-muted max-sm:hidden" aria-hidden="true">
        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 2.5l3 3L6 13H3v-3z"></path></svg>
      </span>
    </span>
  </a>
</li>

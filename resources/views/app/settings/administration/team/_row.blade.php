{{-- One member of the team: who they are, their role, whether they sign in, and the way to manage them. --}}
{{-- @var array{id: int, name: string, email: string|null, staff: \App\Models\Staff, isViewer: bool, role: string|null, state: string, tone: string, manageUrl: string} $row --}}
@php
  $dot = match ($row['tone']) {
    'positive' => 'bg-success',
    'attention' => 'bg-warning',
    default => 'bg-muted-soft',
  };
@endphp

<li
  class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-5 gap-y-2 border-b border-hairline-soft px-5 py-4 transition-colors last:border-b-0 hover:bg-hover md:grid-cols-[minmax(0,1fr)_170px_210px_104px] md:px-6"
>
  <div class="flex min-w-0 items-center gap-3.5">
    <x-avatar :staff="$row['staff']" :name="$row['name']" :size="38" />

    <div class="min-w-0 space-y-0.5">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-[17px] font-bold tracking-tight text-ink">{{ $row['name'] }}</span>

        @if ($row['isViewer'])
          <span class="rounded border border-hairline-strong px-1.25 py-px text-[11px] font-bold tracking-[0.08em] text-muted uppercase">{{ __('You') }}</span>
        @endif
      </div>

      @if ($row['email'] !== null)
        <p class="truncate text-sm text-body">{{ $row['email'] }}</p>
      @endif
    </div>
  </div>

  <div class="col-start-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm md:contents">
    <span class="font-medium text-ink md:text-[15px]">
      <span class="md:sr-only">{{ __('Role') }}:</span>
      {{ $row['role'] ?? __('No role') }}
    </span>

    <span class="flex items-center gap-2 text-body">
      <span class="size-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
      {{ $row['state'] }}
    </span>
  </div>

  <div class="col-start-2 row-span-2 row-start-1 flex justify-end md:col-start-auto md:row-span-1">
    <x-button.secondary :href="$row['manageUrl']" x-target="team-panel" class="py-2">
      {{ __('Manage') }}<span class="sr-only"> {{ $row['name'] }}</span>
    </x-button.secondary>
  </div>
</li>

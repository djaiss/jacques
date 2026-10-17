{{-- One member of the team, where their invitation stands, and what can be done about it. --}}
{{--
  @var array{id: int, name: string, email: string|null, role: string|null, status: string|null, tone: string, sentence: string, actions: list<array{kind: string, label: string, url: string, method: string, confirm: bool}>, canPrepare: bool, roleId: int|null} $row
  @var list<array{id: int, name: string}> $roleOptions
--}}
@php
  $badge = match ($row['tone']) {
    'positive' => 'bg-success/14 text-success ring-success/30',
    'attention' => 'bg-warning/16 text-ink ring-warning/40',
    'muted' => 'bg-hover text-muted ring-hairline',
    default => 'bg-sunken text-body ring-hairline',
  };
@endphp

<div x-data="{ confirming: null }" class="grid gap-x-6 gap-y-3 border-b border-hairline-soft px-5.5 py-4.5 last:border-b-0 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
  <!-- member -->
  <div class="min-w-0 space-y-1">
    <div class="flex flex-wrap items-center gap-x-2.25 gap-y-1">
      <span class="text-[17px] font-bold tracking-tight text-ink">{{ $row['name'] }}</span>

      @if ($row['status'] !== null)
        <span class="rounded-md px-2 py-0.5 text-xs font-bold tracking-wide uppercase ring-1 {{ $badge }}">{{ $row['status'] }}</span>
      @endif
    </div>

    @if ($row['email'] !== null || $row['role'] !== null)
      <p class="text-[15px] wrap-anywhere text-body">{{ collect([$row['email'], $row['role']])->filter()->implode(' · ') }}</p>
    @endif

    <p class="text-sm leading-normal text-pretty text-muted">{{ $row['sentence'] }}</p>
  </div>

  <!-- actions -->
  @if ($row['actions'] !== [] || $row['canPrepare'])
    <div class="flex flex-wrap items-center gap-2 md:justify-end">
      @foreach ($row['actions'] as $action)
        @if ($action['method'] === 'get')
          <x-button.secondary :href="$action['url']" class="py-2">{{ $action['label'] }}</x-button.secondary>
        @elseif ($action['confirm'])
          <x-button.secondary type="button" x-on:click="confirming = '{{ $action['kind'] }}'" aria-haspopup="dialog" class="py-2">{{ $action['label'] }}</x-button.secondary>

          <x-confirm-dialog
            show="confirming === '{{ $action['kind'] }}'"
            close="confirming = null"
            labelledby="team-{{ $row['id'] }}-{{ $action['kind'] }}-title"
            :title="$action['kind'] === 'revoke' ? __('Revoke the invitation of :name?', ['name' => $row['name']]) : __('Cancel the invitation of :name?', ['name' => $row['name']])"
            :cancel="__('Keep it')"
          >
            {{ $action['kind'] === 'revoke'
              ? __('The link already sent stops working. You can prepare a new invitation later.')
              : __('Nothing was sent yet. They stay on the team without an account.') }}

            <x-slot:actions>
              <x-form :method="$action['method']" :action="$action['url']">
                <x-button.danger>{{ $action['label'] }}</x-button.danger>
              </x-form>
            </x-slot:actions>
          </x-confirm-dialog>
        @else
          <x-form :method="$action['method']" :action="$action['url']">
            <x-button.secondary class="py-2">{{ $action['label'] }}</x-button.secondary>
          </x-form>
        @endif
      @endforeach

      @if ($row['canPrepare'])
        <x-form method="post" :action="route('settings.staffInvitations.create', $row['id'])" class="flex flex-wrap items-center gap-2">
          <label for="team-{{ $row['id'] }}-role" class="sr-only">{{ __('Role of the account') }}</label>
          <select id="team-{{ $row['id'] }}-role" name="role_id" class="cursor-pointer rounded-[10px] border-[1.5px] border-control bg-input px-3 py-2 text-[15px] text-ink hover:border-ink focus:border-focus-ring focus:ring-2 focus:ring-focus-ring focus:outline-none">
            @foreach ($roleOptions as $role)
              <option value="{{ $role['id'] }}" @selected($role['id'] === $row['roleId'])>{{ $role['name'] }}</option>
            @endforeach
          </select>

          <x-button.secondary class="py-2">{{ $row['status'] === null ? __('Prepare invitation') : __('Prepare new invitation') }}</x-button.secondary>
        </x-form>
      @endif
    </div>
  @endif
</div>

{{-- The panel that manages one team member: their account, their role and what that role allows. --}}
{{--
  @var array $panel
  @var \App\ViewModels\Settings\Administration\TeamViewModel $viewModel
--}}
@php
  $close = "\$ajax('".$panel['closeUrl']."', { target: 'team-panel' })";
  $dot = match ($panel['tone']) {
    'positive' => 'bg-success',
    'attention' => 'bg-warning',
    default => 'bg-muted-soft',
  };
  $state = '{ role: '.Js::from((string) $panel['roleId']).', confirming: null }';
@endphp

<x-side-modal show="true" :close="$close" labelledby="team-panel-title" :x-data="$state">
  <x-slot:header>
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Manage access') }}</p>
    <h2 id="team-panel-title" class="mt-1 text-[26px] font-bold tracking-[-0.025em] wrap-anywhere text-ink">{{ $panel['name'] }}</h2>

    @if ($panel['email'] !== null)
      <p class="mt-0.5 text-sm wrap-anywhere text-body">{{ $panel['email'] }}</p>
    @endif
  </x-slot>

  <div class="space-y-7">
    @if ($errors->has('panel') || $errors->has('invitation'))
      <!-- refusal -->
      <p role="alert" class="rounded-xl border border-error/40 bg-canvas px-4 py-3 text-sm leading-normal text-pretty text-error">{{ $errors->first('panel') ?: $errors->first('invitation') }}</p>
    @endif

    <!-- account -->
    <section class="space-y-3" aria-labelledby="team-panel-account">
      <h3 id="team-panel-account" class="text-[17px] font-bold tracking-tight text-ink">{{ __('Account') }}</h3>

      <div class="flex items-start gap-2.5 rounded-xl border border-hairline bg-sunken px-4 py-3.5">
        <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>

        <div class="min-w-0 flex-1 space-y-3">
          <div class="space-y-0.5">
            <p class="text-[15px] font-medium text-ink">{{ $panel['state'] }}</p>
            <p class="text-sm leading-normal text-pretty text-body">{{ $panel['sentence'] }}</p>
          </div>

          @if ($panel['actions'] !== [])
            <div class="flex flex-wrap gap-2">
              @foreach ($panel['actions'] as $action)
                @if ($action['confirm'])
                  <x-button.secondary type="button" x-on:click="confirming = '{{ $action['kind'] }}'" aria-haspopup="dialog" class="py-2">{{ $action['label'] }}</x-button.secondary>

                  <x-confirm-dialog
                    show="confirming === '{{ $action['kind'] }}'"
                    close="confirming = null"
                    labelledby="team-panel-{{ $action['kind'] }}-title"
                    :title="$action['kind'] === 'remove' ? __('Remove the account of :name?', ['name' => $panel['name']]) : __('Cancel the invitation of :name?', ['name' => $panel['name']])"
                    :cancel="__('Keep it')"
                  >
                    {{ $action['kind'] === 'remove'
                      ? __('They can no longer sign in, but they stay on the team and can still be booked.')
                      : __('The link stops working. They stay on the team without an account.') }}

                    <x-slot:actions>
                      <x-form :method="$action['method']" :action="$action['url']">
                        <x-button.danger>{{ $action['kind'] === 'remove' ? __('Remove it') : __('Cancel it') }}</x-button.danger>
                      </x-form>
                    </x-slot:actions>
                  </x-confirm-dialog>
                @else
                  <x-form :method="$action['method']" :action="$action['url']">
                    @if ($action['withRole'])
                      <input type="hidden" name="role_id" :value="role" />
                    @endif

                    @if ($action['primary'])
                      <x-button class="py-2">{{ $action['label'] }}</x-button>
                    @else
                      <x-button.secondary class="py-2">{{ $action['label'] }}</x-button.secondary>
                    @endif
                  </x-form>
                @endif
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>

    <x-form method="put" :action="$panel['roleUrl']" class="space-y-7">
      <!-- role picker -->
      <fieldset class="space-y-3">
        <legend class="space-y-1">
          <span class="block text-[17px] font-bold tracking-tight text-ink">{{ __('Role') }}</span>
          <span class="block text-sm text-body">{{ __('A role sets everything this person may do.') }}</span>
        </legend>

        @if ($panel['isSoleOwner'])
          <x-notice>{{ __('You are the only owner of :salon. Make somebody else an owner before you change this role.', ['salon' => $viewModel->companyName()]) }}</x-notice>
        @endif

        <div class="grid gap-2 sm:grid-cols-2">
          @foreach ($panel['roles'] as $role)
            <label
              :class="role === '{{ $role['id'] }}' ? 'border-ink' : 'border-hairline'"
              class="relative flex flex-col gap-1.5 rounded-xl border-[1.5px] bg-canvas px-3.5 py-3 has-focus-visible:ring-2 has-focus-visible:ring-focus-ring {{ $role['disabled'] ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}"
            >
              <span class="flex items-center justify-between gap-1.5">
                <span class="text-[15px] font-bold tracking-tight text-ink">{{ $role['name'] }}</span>
                <input
                  type="radio"
                  name="role_id"
                  value="{{ $role['id'] }}"
                  x-model="role"
                  @disabled($role['disabled'])
                  class="size-3.5 accent-ink"
                />
              </span>

              @if ($role['description'] !== null)
                <span class="text-[13px] leading-snug text-pretty text-body">{{ $role['description'] }}</span>
              @endif
            </label>
          @endforeach
        </div>
      </fieldset>

      <!-- role permissions -->
      <section class="space-y-3" aria-labelledby="team-panel-permissions">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <div class="space-y-1">
            <h3 id="team-panel-permissions" class="text-[17px] font-bold tracking-tight text-ink">{{ __('Permissions') }}</h3>
            <p class="text-sm text-body">{{ __('What the chosen role allows. They change with the role, for everybody who holds it.') }}</p>
          </div>

          @foreach ($panel['roleEditorUrls'] as $roleId => $url)
            <a x-cloak x-show="role === '{{ $roleId }}'" href="{{ $url }}" class="text-sm text-ink underline decoration-hairline-strong underline-offset-3 hover:text-accent-ink">{{ __('Edit this role') }}</a>
          @endforeach
        </div>

        @foreach ($panel['permissions'] as $roleId => $groups)
          <div x-cloak x-show="role === '{{ $roleId }}'" class="overflow-hidden rounded-xl border border-hairline">
            @foreach ($groups as $group)
              <div class="space-y-2.5 border-b border-hairline-soft px-4 py-3.5 last:border-b-0">
                <h4 class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ $group['title'] }}</h4>

                <ul class="space-y-1.5">
                  @foreach ($group['permissions'] as $permission)
                    <li class="flex items-start justify-between gap-3 text-[15px] {{ $permission['allowed'] ? 'text-ink' : 'text-muted' }}">
                      <span class="flex items-start gap-2">
                        @if ($permission['allowed'])
                          <svg width="14" height="14" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="mt-1 shrink-0 text-success" aria-hidden="true"><path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path></svg>
                        @else
                          <svg width="14" height="14" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="mt-1 shrink-0" aria-hidden="true"><path d="M3 6h6"></path></svg>
                        @endif
                        <span>
                          {{ $permission['label'] }}
                          <span class="sr-only">: {{ $permission['allowed'] ? __('allowed') : __('not allowed') }}</span>
                        </span>
                      </span>

                      @if ($permission['scope'] !== null)
                        <span class="shrink-0 text-xs text-muted">{{ $permission['scope'] }}</span>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>
            @endforeach
          </div>
        @endforeach
      </section>

      <!-- panel actions -->
      <div class="flex justify-end gap-2.5 pt-1">
        <x-button.secondary type="button" x-on:click="{{ $close }}">{{ __('Cancel') }}</x-button.secondary>

        @if ($panel['canSave'])
          <x-button>{{ __('Save changes') }}</x-button>
        @endif
      </div>
    </x-form>
  </div>
</x-side-modal>

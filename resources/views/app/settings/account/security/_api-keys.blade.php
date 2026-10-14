{{-- The API keys somebody has minted, and the way to make another or revoke one. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div
  id="api-keys"
  x-merge="replace"
  x-data="{ creating: {{ $errors->has('name') ? 'true' : 'false' }}, revoking: null }"
  class="space-y-6 transition-opacity [&[aria-busy]]:opacity-60"
>
  <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
    <p>{{ __('A key lets a script or another system act as you, with exactly the access you have.') }}</p>
  </div>

  <!-- the key just made -->
  @if (session('apiKey'))
    <div tabindex="-1" x-autofocus class="space-y-2 rounded-xl bg-sunken px-4 py-3.5 ring-[1.5px] ring-hairline focus:outline-none">
      <p class="text-[15px] font-semibold text-ink">{{ __('Your new API key') }}</p>

      <p class="text-sm text-muted">{{ __('This is the only time we can show it to you. Copy it somewhere safe before you leave this screen.') }}</p>

      <p class="rounded-md border border-hairline bg-canvas px-3 py-2 font-mono text-sm break-all text-ink select-all">{{ session('apiKey') }}</p>
    </div>
  @endif

  <!-- notice about the api -->
  <x-notice>{{ __('There is nothing to point a key at yet. The API that accepts them is still being built, and a key you make now works the moment it lands.') }}</x-notice>

  <!-- list of keys -->
  <div class="overflow-hidden rounded-xl ring-[1.5px] ring-hairline">
    <x-box.row class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
      <p class="text-sm font-semibold text-ink">{{ $viewModel->apiKeysHeader() }}</p>

      <x-button.secondary
        type="button"
        x-ref="createButton"
        x-show="! creating"
        @click="creating = true; $nextTick(() => $refs.name.focus())"
        class="max-sm:w-full"
      >{{ __('Create key') }}</x-button.secondary>
    </x-box.row>

    <div x-cloak x-show="creating" class="border-b border-hairline-soft px-4 py-3.5">
      <x-form method="post" :action="route('settings.apiKeys.create')" x-target="api-keys" class="space-y-3.5">
        <x-input
          id="name"
          x-ref="name"
          :label="__('Name')"
          :placeholder="__('Deployment script')"
          :help="__('Something you will recognise in a year, such as the machine or the script that uses it.')"
          :error="$errors->get('name')"
          maxlength="255"
          required
        />

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <x-button.secondary type="button" @click="creating = false; $nextTick(() => $refs.createButton.focus())">{{ __('Cancel') }}</x-button.secondary>

          <x-button>{{ __('Create key') }}</x-button>
        </div>
      </x-form>
    </div>

    @forelse ($viewModel->apiKeys() as $apiKey)
      <x-box.row class="grid gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <div class="flex items-center gap-x-3">
          <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-sunken text-placeholder" aria-hidden="true">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
              <circle cx="10.4" cy="5.6" r="3"></circle>
              <path d="M8.3 7.7 3 13"></path>
              <path d="M4.6 11.4 6 12.8"></path>
            </svg>
          </span>

          <div class="min-w-0 space-y-0.5">
            <p class="text-sm font-semibold wrap-anywhere text-ink">{{ $apiKey['name'] }}</p>

            <p class="text-sm text-muted">
              @if ($apiKey['lastUsedAt'])
                {{ __('Created :created · last used :used', ['created' => $apiKey['createdAt'], 'used' => $apiKey['lastUsedAt']]) }}
              @else
                {{ __('Created :created · never used', ['created' => $apiKey['createdAt']]) }}
              @endif
            </p>
          </div>
        </div>

        <x-button.secondary type="button" @click="revoking = {{ $apiKey['id'] }}" aria-haspopup="dialog" class="max-sm:w-full">{{ __('Revoke') }}<span class="sr-only"> {{ $apiKey['name'] }}</span></x-button.secondary>
      </x-box.row>
    @empty
      <x-empty-state :title="__('No API keys yet')">
        <x-slot:icon>
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
            <circle cx="10.4" cy="5.6" r="3"></circle>
            <path d="M8.3 7.7 3 13"></path>
            <path d="M4.6 11.4 6 12.8"></path>
          </svg>
        </x-slot:icon>

        {{ __('Make one when you have something to point it at. The key is shown once, when you make it.') }}
      </x-empty-state>
    @endforelse
  </div>

  <!-- revoke dialogs -->
  @foreach ($viewModel->apiKeys() as $apiKey)
    <x-confirm-dialog
      show="revoking === {{ $apiKey['id'] }}"
      close="revoking = null"
      labelledby="revoke-api-key-{{ $apiKey['id'] }}-title"
      :title="__('Revoke :name?', ['name' => $apiKey['name']])"
      :cancel="__('Keep it')"
    >
      {{ __('Anything still signing in with this key stops working straight away. A revoked key cannot be brought back, only replaced.') }}

      <x-slot:actions>
        <x-form method="delete" :action="route('settings.apiKeys.destroy', $apiKey['id'])" x-target="api-keys">
          <x-button.danger>{{ __('Revoke it') }}</x-button.danger>
        </x-form>
      </x-slot:actions>
    </x-confirm-dialog>
  @endforeach
</div>

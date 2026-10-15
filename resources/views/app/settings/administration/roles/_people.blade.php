{{-- Who holds the role, and the two things that can be done about it. --}}
{{--
  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
@php
  $people = $viewModel->people();
  $assignable = $viewModel->assignable();
@endphp

<div x-data="{ assigning: false, removing: null }">
  <!-- title and the assign button -->
  <div class="mb-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
    <h2 class="text-[22px] leading-tight font-bold tracking-tight text-ink">{{ __('Held by') }}</h2>

    <x-button.secondary type="button" x-on:click="assigning = true" :disabled="$assignable === []">
      {{ $assignable === [] ? __('Everybody holds it') : __('Assign people') }}
    </x-button.secondary>
  </div>

  <!-- list of people -->
  <div class="rounded-[18px] bg-canvas px-2.5 py-2 ring-[1.5px] ring-hairline">
    @forelse ($people as $person)
      <div class="flex flex-wrap items-center gap-x-3.5 gap-y-2 rounded-xl px-3 py-2.75">
        <x-avatar :staff="$person['staff']" :name="$person['name']" :size="34" />

        <div class="min-w-0 flex-1">
          <p class="text-base font-semibold wrap-anywhere text-ink">{{ $person['name'] }}</p>

          <p class="text-sm text-muted">{{ $person['since'] }}</p>
        </div>

        @if ($person['isRemovable'])
          <button
            type="button"
            x-on:click="removing = {{ $person['id'] }}"
            aria-haspopup="dialog"
            class="cursor-pointer rounded-md px-1.5 py-1 text-[15px] font-semibold text-muted transition-colors hover:text-error focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
          >{{ __('Remove') }}<span class="sr-only"> {{ $person['name'] }}</span></button>
        @else
          <p class="text-sm text-muted">{{ __('The last owner keeps the role. Give it to somebody else first.') }}</p>
        @endif
      </div>
    @empty
      <x-empty-state :title="__('Nobody holds this role yet')">
        <x-slot:icon>
          <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
            <circle cx="8" cy="6" r="2.6"></circle>
            <path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>
          </svg>
        </x-slot:icon>

        {{ __('Assign it to somebody to put its permissions to work. Until then it grants nothing to anybody.') }}
      </x-empty-state>
    @endforelse
  </div>

  <!-- note about handing roles out -->
  <p class="mt-3 text-sm leading-relaxed text-pretty text-muted">
    {{ __('Handing a role out and taking it back are both written to the logs, on both sides.') }}
  </p>

  @include('app.settings.administration.roles._assign-people', ['viewModel' => $viewModel, 'role' => $role, 'assignable' => $assignable])

  <!-- take the role back dialogs -->
  @foreach (array_filter($people, fn (array $person): bool => $person['isRemovable']) as $person)
    <x-confirm-dialog
      show="removing === {{ $person['id'] }}"
      close="removing = null"
      labelledby="remove-holder-{{ $person['id'] }}-title"
      :title="__('Take the role back from :name?', ['name' => $person['name']])"
      :cancel="__('Keep it')"
    >
      {{ __('They keep whatever their other roles grant. Anything only this role gave them, they lose.') }}

      <x-slot:actions>
        <x-form method="delete" :action="$person['removeUrl']">
          <x-button.danger>{{ __('Take it back') }}</x-button.danger>
        </x-form>
      </x-slot:actions>
    </x-confirm-dialog>
  @endforeach
</div>

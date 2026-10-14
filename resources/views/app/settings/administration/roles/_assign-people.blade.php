{{-- The dialog that hands the role to somebody. --}}
{{--
  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
  @var array $assignable
--}}
<div
  x-cloak
  x-show="assigning"
  x-transition.opacity.duration.110ms
  x-on:keydown.escape="assigning = false"
  x-on:click.self="assigning = false"
  :data-escape-guard="assigning ? '' : null"
  class="fixed inset-0 z-70 flex justify-center overflow-y-auto bg-black/35 px-4 py-14"
>
  <div
    x-trap.inert="assigning"
    role="dialog"
    aria-modal="true"
    aria-labelledby="assign-people-title"
    class="h-fit w-full max-w-115 rounded-[20px] bg-canvas px-6 pt-6 pb-7 shadow-2xl ring-[1.5px] ring-hairline sm:px-7"
  >
    <h2 id="assign-people-title" class="text-2xl font-bold tracking-tight text-ink">
      {{ __('Assign :role', ['role' => $role['name']]) }}
    </h2>

    <p class="mt-1.5 text-[15px] leading-relaxed text-pretty text-body">
      {{ __('Pick who holds it. Handing a role out is written to the logs, on both sides.') }}
    </p>

    <div class="mt-5 -mx-2">
      @foreach ($assignable as $person)
        <x-form method="post" :action="$role['assignUrl']">
          <input type="hidden" name="user" value="{{ $person['id'] }}" />

          <button
            type="submit"
            class="flex w-full cursor-pointer items-center gap-3.5 rounded-xl px-3 py-2.75 text-left transition-colors hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
          >
            <x-avatar :employee="$person['employee']" :name="$person['name']" :size="32" />

            <span class="min-w-0 flex-1">
              <span class="block text-base font-semibold wrap-anywhere text-ink">{{ $person['name'] }}</span>

              @if ($person['email'] !== $person['name'])
                <span class="block text-sm wrap-anywhere text-muted">{{ $person['email'] }}</span>
              @endif
            </span>

            <span class="text-[15px] font-semibold text-accent-ink">{{ __('Add') }}</span>
          </button>
        </x-form>
      @endforeach
    </div>

    <div class="mt-5">
      <x-button.secondary type="button" x-on:click="assigning = false">{{ __('Done') }}</x-button.secondary>
    </div>
  </div>
</div>

{{-- The one thing done to a role as a whole that belongs nowhere else: deleting it. --}}
{{--
  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
<div
  x-data="{ open: false }"
  x-on:click.outside="open = false"
  x-on:keydown.escape="if (open) { open = false; $refs.trigger.focus() }"
  x-on:focusout="if (! $el.contains($event.relatedTarget)) { open = false }"
  :data-escape-guard="open ? '' : null"
  class="relative"
>
  <button
    type="button"
    x-ref="trigger"
    x-on:click="open = ! open"
    :aria-expanded="open ? 'true' : 'false'"
    aria-controls="role-menu"
    aria-label="{{ __('More about this role') }}"
    class="flex size-11 cursor-pointer items-center justify-center rounded-[10px] border-[1.5px] border-hairline-strong bg-card text-body transition-colors hover:border-ink hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
  >
    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
      <circle cx="3.6" cy="8" r="1.35"></circle>
      <circle cx="8" cy="8" r="1.35"></circle>
      <circle cx="12.4" cy="8" r="1.35"></circle>
    </svg>
  </button>

  <div
    x-cloak
    x-show="open"
    x-transition.opacity.duration.120ms
    id="role-menu"
    class="absolute top-full right-0 z-20 mt-2 w-72 rounded-[18px] bg-canvas p-2 shadow-2xl ring-[1.5px] ring-hairline"
  >
    @if ($viewModel->canBeDeleted())
      <button
        type="button"
        x-on:click="open = false; $refs.trigger.focus(); deleting = true"
        aria-haspopup="dialog"
        class="flex w-full cursor-pointer items-center rounded-xl px-3 py-2.5 text-left text-[15px] font-semibold text-error transition-colors hover:bg-hover"
      >{{ __('Delete this role') }}</button>
    @else
      <button
        type="button"
        aria-disabled="true"
        aria-describedby="role-delete-hint"
        class="flex w-full cursor-not-allowed items-center gap-2 rounded-xl px-3 py-2.5 text-left text-[15px] text-muted-soft"
      >
        {{ __('Delete this role') }}

        <span id="role-delete-hint" class="ml-auto text-sm">{{ $viewModel->deleteHint() }}</span>
      </button>
    @endif
  </div>
</div>

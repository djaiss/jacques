{{-- One role: what it is allowed to do under one tab, and who holds it under the other. --}}
{{-- @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel --}}
@php
  $role = $viewModel->role();
  $onPeopleTab = $viewModel->onPeopleTab();
@endphp

<x-top-bar-layout :title="$role['name']">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Roles') => route('settings.roles.index'),
      $role['name'] => null,
    ]"
  />

  <div
    x-data="{
      deleting: false,
      granted: @js($viewModel->grantedPermissions()),
      grantCounts: @js($viewModel->grantCountLabels()),
      tabCounts: @js($viewModel->permissionTabLabels()),
      grantedCount() { return Object.values(this.granted).filter(Boolean).length },
    }"
  >
    <!-- role name and its buttons -->
    <div class="mb-6 grid gap-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
      <div class="flex min-w-0 items-center gap-4">
        <span class="accent-tile grid size-13 shrink-0 place-items-center rounded-2xl" style="--tile-hue: {{ $role['hue'] }}">
          <x-settings-icon name="roles" />
        </span>

        <div class="min-w-0 space-y-1.5">
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            @if ($onPeopleTab)
              <h1 class="text-[32px] leading-tight font-bold tracking-tight text-ink">{{ $role['name'] }}</h1>
            @else
              <h1 class="sr-only">{{ $role['name'] }}</h1>

              <label for="role-name" class="sr-only">{{ __('Name of the role') }}</label>

              <input
                type="text"
                id="role-name"
                name="name"
                form="role-form"
                value="{{ $role['name'] }}"
                maxlength="255"
                required
                @disabled(! $role['isEditable'])
                @if ($errors->has('name')) aria-invalid="true" aria-describedby="role-name-error" @endif
                class="-mx-2.5 min-w-40 flex-1 rounded-[10px] border-[1.5px] border-transparent bg-transparent px-2.5 py-1 text-[32px] leading-tight font-bold tracking-tight text-ink transition-colors outline-none hover:border-hairline-strong focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring disabled:hover:border-transparent"
              />
            @endif

            @foreach ($role['badges'] as $badge)
              <span class="rounded-md px-2 py-0.5 text-xs font-bold tracking-wide uppercase {{ $badge['tone'] === 'accent' ? 'bg-accent-soft text-accent-ink' : 'bg-hover text-muted ring-1 ring-hairline' }}">
                {{ $badge['label'] }}
              </span>
            @endforeach
          </div>

          <x-error :id="$errors->has('name') ? 'role-name-error' : null" :messages="$errors->get('name')" />
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        @if (! $onPeopleTab && $role['isEditable'])
          <x-button form="role-form">{{ __('Save changes') }}</x-button>
        @endif

        <x-form method="post" :action="$role['duplicateUrl']">
          <x-button.secondary>{{ __('Duplicate') }}</x-button.secondary>
        </x-form>

        @include('app.settings.administration.roles._role-menu', ['viewModel' => $viewModel, 'role' => $role])
      </div>
    </div>

    <!-- warning about full access -->
    @if ($viewModel->warnsAboutAdministration())
      <div class="mb-6 flex items-start gap-3 rounded-[14px] bg-error/8 px-4 py-3.5 ring-[1.5px] ring-error/25">
        <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-0.5 shrink-0 text-error" aria-hidden="true">
          <path d="M8 2.6 14.2 13H1.8L8 2.6Z"></path>
          <line x1="8" y1="6.6" x2="8" y2="9.4"></line>
          <circle cx="8" cy="11.2" r="0.5" fill="currentColor"></circle>
        </svg>

        <p class="text-[15px] leading-relaxed text-pretty text-error">
          {{ __('Anybody holding this role can grant themselves every other permission. Treat it as full access.') }}
        </p>
      </div>
    @endif

    <!-- permissions or people -->
    <div class="mb-5 flex justify-center">
      <nav aria-label="{{ __('Role sections') }}" class="flex gap-0.75 rounded-xl bg-canvas p-1 ring-[1.5px] ring-hairline">
        @foreach ($viewModel->tabs() as $tab)
          <a
            href="{{ $tab['url'] }}"
            data-turbo="true"
            @if ($tab['current']) aria-current="page" @endif
            @if ($tab['key'] === 'permissions') x-text="tabCounts[grantedCount()]" @endif
            class="rounded-[9px] px-3.5 py-2 text-[15px] font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none {{ $tab['current'] ? 'bg-nav-hover text-ink' : 'text-muted hover:text-ink' }}"
          >
            {{ $tab['label'] }}
          </a>
        @endforeach
      </nav>
    </div>

    <!-- the tab being read -->
    <div class="mx-auto max-w-180">
      @if ($onPeopleTab)
        @include('app.settings.administration.roles._people', ['viewModel' => $viewModel, 'role' => $role])
      @else
        <x-form method="put" :action="$role['updateUrl']" id="role-form">
          @include('app.settings.administration.roles._permissions', ['viewModel' => $viewModel, 'role' => $role])
        </x-form>
      @endif
    </div>

    @include('app.settings.administration.roles._delete-role', ['role' => $role])
  </div>
</x-top-bar-layout>

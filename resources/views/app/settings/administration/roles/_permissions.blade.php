{{-- The matrix: everything a role can be allowed to do, whether this one is, and over whom. --}}
{{--
  @var \App\ViewModels\Settings\Administration\RolesViewModel $viewModel
  @var array $role
--}}
@php
  $groups = $viewModel->groups();

  /* What a row is matched against, and which rows belong to which group, so the
     filter can hide a whole section once nothing under it matches. */
  $search = [];
  $sections = [];
  $counts = [];

  foreach ($groups as $group) {
    $sections[$group['title']] = array_column($group['permissions'], 'value');
    $counts[$group['title']] = $group['counts'];

    foreach ($group['permissions'] as $permission) {
      $search[$permission['value']] = mb_strtolower($permission['value'].' '.$permission['label']);
    }
  }

  $bars = [
    'none' => 'bg-error',
    'partial' => 'bg-warning',
    'full' => 'bg-success',
  ];
@endphp

<div
  x-data="{
    query: '',
    collapsed: {},
    search: @js($search),
    sections: @js($sections),
    counts: @js($counts),
    term() { return this.query.trim().toLowerCase() },
    matches(value) { return this.term() === '' || this.search[value].includes(this.term()) },
    sectionMatches(title) { return this.sections[title].some((value) => this.matches(value)) },
    open(title) { return this.term() !== '' || ! this.collapsed[title] },
    nothingMatches() { return ! Object.keys(this.search).some((value) => this.matches(value)) },
    grantedIn(title) { return this.sections[title].filter((value) => this.granted[value]).length },
    barWidth(title) { return this.grantedIn(title) === 0 ? '9px' : Math.round(this.grantedIn(title) / this.sections[title].length * 100) + '%' },
    barTone(title) {
      if (this.grantedIn(title) === 0) {
        return 'none'
      }

      return this.grantedIn(title) < this.sections[title].length ? 'partial' : 'full'
    },
  }"
>
  <!-- title and the count granted -->
  <div class="mb-3 flex flex-wrap items-end justify-between gap-x-4 gap-y-1">
    <h2 class="text-[22px] leading-tight font-bold tracking-tight text-ink">{{ __('Allowed to') }}</h2>

    <p aria-live="polite" class="text-[15px] text-muted" x-text="grantCounts[grantedCount()]">{{ $viewModel->grantCountLabel() }}</p>
  </div>

  <label for="permission-filter" class="sr-only">{{ __('Filter permissions') }}</label>

  <input
    type="search"
    id="permission-filter"
    x-model="query"
    x-on:input.stop
    x-on:keydown.enter.prevent
    placeholder="{{ __('Filter permissions') }}"
    class="mb-3 block w-full appearance-none rounded-xl border-[1.5px] border-control bg-input px-3.5 py-2.5 text-base text-ink placeholder-placeholder transition-colors duration-150 hover:border-ink hover:bg-hover focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
  />

  <!-- the permission matrix -->
  <div class="rounded-[18px] bg-canvas px-2.5 py-2 ring-[1.5px] ring-hairline">
    @foreach ($groups as $group)
      <div x-show="sectionMatches(@js($group['title']))">
        <button
          type="button"
          x-on:click="collapsed[@js($group['title'])] = ! collapsed[@js($group['title'])]"
          :aria-expanded="open(@js($group['title'])) ? 'true' : 'false'"
          class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
        >
          <svg
            width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
            class="shrink-0 text-muted transition-transform"
            :class="open(@js($group['title'])) ? '' : '-rotate-90'"
            aria-hidden="true"
          >
            <path d="M4.4 6.4 8 10l3.6-3.6"></path>
          </svg>

          <span class="flex min-w-0 flex-wrap items-baseline gap-x-3 gap-y-0.5">
            <span class="text-[13px] font-bold tracking-[0.08em] text-body uppercase">{{ $group['title'] }}</span>

            <span class="text-[13px] text-muted-soft">{{ $group['note'] }}</span>
          </span>

          <span class="ml-auto shrink-0 text-[13px] text-muted tabular-nums" x-text="counts[@js($group['title'])][grantedIn(@js($group['title']))]">{{ $group['count'] }}</span>

          <span class="block h-1.5 w-16 shrink-0 overflow-hidden rounded-full bg-sunken" aria-hidden="true">
            <span
              class="block h-full rounded-full {{ $bars[$group['tone']] }}"
              style="width: {{ $group['width'] }}"
              :style="{ width: barWidth(@js($group['title'])) }"
              :class="{
                'bg-error': barTone(@js($group['title'])) === 'none',
                'bg-warning': barTone(@js($group['title'])) === 'partial',
                'bg-success': barTone(@js($group['title'])) === 'full',
              }"
            ></span>
          </span>
        </button>

        @foreach ($group['permissions'] as $permission)
          <div
            x-show="open(@js($group['title'])) && matches(@js($permission['value']))"
            class="grid gap-x-3.5 gap-y-1.5 rounded-xl py-2.5 pr-3 pl-8.5 transition-colors hover:bg-hover sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
          >
            <label class="flex min-w-0 cursor-pointer items-center gap-3.5">
              <input
                type="checkbox"
                name="permissions[{{ $permission['value'] }}][granted]"
                value="1"
                x-model="granted[@js($permission['value'])]"
                @checked($permission['granted'])
                @disabled(! $role['isEditable'])
                class="peer sr-only"
              />

              <span
                aria-hidden="true"
                class="flex size-5.5 shrink-0 items-center justify-center rounded-[7px] bg-canvas text-transparent ring-[1.5px] ring-control transition-colors peer-disabled:opacity-60 peer-checked:bg-ink peer-checked:text-canvas peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring"
              >
                <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
                </svg>
              </span>

              <span class="min-w-0 text-base leading-snug font-medium text-ink">{{ $permission['label'] }}</span>
            </label>

            @if ($permission['scopes'] !== [])
              <fieldset
                x-cloak
                x-show="granted[@js($permission['value'])]"
                class="flex flex-wrap gap-1 rounded-[10px] bg-sunken p-0.75 max-sm:ml-9 max-sm:justify-self-start"
              >
                <legend class="sr-only">{{ __('Scope of :permission', ['permission' => $permission['label']]) }}</legend>

                @foreach ($permission['scopes'] as $value => $label)
                  <label class="cursor-pointer">
                    <input
                      type="radio"
                      name="permissions[{{ $permission['value'] }}][scope]"
                      value="{{ $value }}"
                      @checked($permission['scope'] === $value)
                      @disabled(! $role['isEditable'])
                      class="peer sr-only"
                    />

                    <span class="block rounded-[7px] px-2.5 py-1 text-sm font-semibold whitespace-nowrap text-muted transition-colors peer-checked:bg-canvas peer-checked:text-ink peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-hairline peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring peer-disabled:cursor-not-allowed hover:text-ink">{{ $label }}</span>
                  </label>
                @endforeach
              </fieldset>
            @else
              <p x-cloak x-show="granted[@js($permission['value'])]" class="text-sm font-semibold whitespace-nowrap text-muted-soft max-sm:pl-9">{{ __('Everybody in the company, always') }}</p>
            @endif
          </div>
        @endforeach
      </div>
    @endforeach

    <p x-cloak x-show="nothingMatches()" role="status" class="px-3 py-6 text-center text-[15px] text-muted">{{ __('No permission matches that.') }}</p>
  </div>

  <!-- note about scopes -->
  <p class="mt-3 text-sm leading-relaxed text-pretty text-muted">
    {{ __('The choice on the right says who a permission reaches: the person themselves, the locations they are assigned to, or the whole company. A few permissions cannot be narrowed and always cover the whole company.') }}
  </p>
</div>

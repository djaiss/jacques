{{-- The form that adds or edits a service: its texts, category, prices, steps, salons and people. --}}
{{-- @var \App\ViewModels\Services\ServiceFormViewModel $viewModel --}}
@php
  $first = $viewModel->firstLanguage();
  $field = 'block w-full rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink placeholder:text-placeholder hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none';
  $iconButton = 'flex size-7 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none disabled:cursor-default disabled:opacity-35 disabled:hover:bg-transparent';
  $salonState = collect($viewModel->salons())->mapWithKeys(fn (array $salon): array => [
    $salon['id'] => ['on' => $salon['isOn'], 'people' => collect($salon['people'])->filter(fn (array $person): bool => $person['isOn'])->pluck('id')->values()->all(), 'everyone' => collect($salon['people'])->pluck('id')->all()],
  ])->all();
@endphp

<x-form
  method="{{ $viewModel->method() }}"
  :action="$viewModel->action()"
  id="service-form"
  class="space-y-10"
  x-data="{
    language: {{ Js::from($first) }},
    steps: {{ Js::from($viewModel->steps()) }},
    names: {{ Js::from($viewModel->names()) }},
    processingName: {{ Js::from($viewModel->processingName()) }},
    salons: {{ Js::from((object) $salonState) }},
    total() { return this.steps.reduce((sum, step) => sum + (Number(step.duration) || 0), 0) },
    busy() { return this.steps.filter((step) => step.requires_staff).reduce((sum, step) => sum + (Number(step.duration) || 0), 0) },
    breakUp() {
      this.steps = [
        { name: { ...this.names }, duration: Number(this.steps[0].duration) || 30, requires_staff: true },
        { name: { ...this.processingName }, duration: 15, requires_staff: false },
      ]
    },
    addStep() { this.steps.push({ name: {}, duration: 15, requires_staff: true }) },
    move(index, offset) {
      const steps = [...this.steps];
      [steps[index], steps[index + offset]] = [steps[index + offset], steps[index]]
      this.steps = steps
    },
    remove(index) {
      if (this.steps.length === 2) {
        this.steps = [{ name: {}, duration: this.steps[1 - index].duration, requires_staff: true }]
      } else {
        this.steps.splice(index, 1)
      }
    },
    isLastBusyStep(index) {
      return this.steps[index].requires_staff && this.steps.filter((step) => step.requires_staff).length === 1
    },
    isEveryone(id) { return this.salons[id].people.length === this.salons[id].everyone.length },
    toggleEveryone(id) { this.salons[id].people = this.isEveryone(id) ? [] : [...this.salons[id].everyone] },
  }"
>
  @if ($errors->any())
    <ul role="alert" class="space-y-1 rounded-xl bg-error/8 px-3.5 py-3 text-sm text-error ring-[1.5px] ring-error/25">
      @foreach ($errors->all() as $message)
        <li>{{ $message }}</li>
      @endforeach
    </ul>
  @endif

  <!-- details -->
  <section class="space-y-3.5" aria-labelledby="service-details-title">
    <div class="space-y-1">
      <h2 id="service-details-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Details') }}</h2>
      <p class="text-[15px] text-pretty text-body">{{ __('What the client sees when they book it, and how long it blocks the agenda.') }}</p>
    </div>

    <div class="space-y-5 rounded-[18px] bg-canvas p-6 ring-[1.5px] ring-hairline">
      @if ($viewModel->hasSeveralLanguages())
        <!-- language picker -->
        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Language of the texts') }}">
          @foreach ($viewModel->languages() as $code)
            <button
              type="button"
              x-on:click="language = @js($code)"
              x-bind:aria-pressed="language === @js($code)"
              class="flex h-8 cursor-pointer items-center gap-1.75 rounded-lg border border-hairline bg-transparent px-2.5 text-sm font-semibold text-ink hover:border-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none aria-pressed:border-ink aria-pressed:bg-page"
            >
              <span class="block h-3 w-4.5 rounded-xs ring-1 ring-black/10" style="background: {{ $viewModel->languageFlag($code) }}" aria-hidden="true"></span>
              {{ $viewModel->languageLabel($code) }}
            </button>
          @endforeach
        </div>
      @endif

      <div class="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)]">
        @foreach ($viewModel->names() as $code => $name)
          <div class="space-y-1.5" x-show="language === @js($code)" @if (! $loop->first) x-cloak @endif>
            <x-label for="service-name-{{ $code }}">{{ $viewModel->hasSeveralLanguages() ? __('Name (:language)', ['language' => $viewModel->languageLabel($code)]) : __('Name') }}</x-label>
            <input id="service-name-{{ $code }}" type="text" name="name[{{ $code }}]" x-model="names[@js($code)]" maxlength="255" placeholder="{{ __("Men's Haircut") }}" class="{{ $field }}" @error("name.{$code}") aria-invalid="true" aria-describedby="service-name-{{ $code }}-error" @enderror />
            @error("name.{$code}")
              <p id="service-name-{{ $code }}-error" class="text-sm text-error">{{ $message }}</p>
            @enderror
          </div>
        @endforeach

        <div class="space-y-1.5">
          <x-label for="service-category">{{ __('Category') }}</x-label>
          <select id="service-category" name="category" class="{{ $field }} cursor-pointer">
            <option value="" @selected($viewModel->categoryId() === null)>{{ __('Without a category') }}</option>
            @foreach ($viewModel->categoryOptions() as $option)
              <option value="{{ $option['id'] }}" @selected($option['id'] === $viewModel->categoryId())>{{ $option['label'] }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <!-- prices -->
      <div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-4">
        @foreach ($viewModel->prices() as $price)
          <div class="space-y-1.5">
            <x-label for="service-price-{{ $price['code'] }}">{{ __('Price in :currency', ['currency' => $price['symbol']]) }}</x-label>
            <input id="service-price-{{ $price['code'] }}" type="text" inputmode="decimal" name="prices[{{ $price['code'] }}]" value="{{ $price['amount'] }}" class="{{ $field }} tabular-nums" />
          </div>
        @endforeach
      </div>

      @foreach ($viewModel->descriptions() as $code => $description)
        <div class="space-y-1.5" x-show="language === @js($code)" @if (! $loop->first) x-cloak @endif>
          <x-label for="service-description-{{ $code }}">
            {{ $viewModel->hasSeveralLanguages() ? __('Description (:language)', ['language' => $viewModel->languageLabel($code)]) : __('Description') }}
            <span class="font-normal text-muted">{{ __('(optional)') }}</span>
          </x-label>
          <textarea id="service-description-{{ $code }}" name="description[{{ $code }}]" rows="3" maxlength="1000" placeholder="{{ __('What is included, and anything the client should know before coming in.') }}" aria-describedby="service-description-help" class="{{ $field }} resize-y leading-normal">{{ $description }}</textarea>
        </div>
      @endforeach
      <p id="service-description-help" class="-mt-3 text-xs text-muted">{{ __('Shown to the client when they book it.') }}</p>

      <!-- duration and steps -->
      <div class="space-y-3.5 border-t border-hairline-soft pt-5">
        <div class="flex items-baseline justify-between gap-3">
          <h3 class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Duration and steps') }}</h3>
          <p class="text-sm font-semibold text-ink tabular-nums"><span x-text="total()"></span> {{ __('min') }}</p>
        </div>

        <template x-if="steps.length === 1">
          <div class="space-y-3.5">
            <div class="max-w-50 space-y-1.5">
              <x-label for="service-duration">{{ __('Duration (minutes)') }}</x-label>
              <input id="service-duration" type="text" inputmode="numeric" maxlength="4" name="steps[0][duration]" x-model="steps[0].duration" class="{{ $field }} tabular-nums" />
              <input type="hidden" name="steps[0][requires_staff]" value="1" />
            </div>
            <p class="text-sm leading-normal text-pretty text-body">{{ __('Some services include processing time when your team member can attend to another client. Break a service into steps to manage availability more accurately.') }}</p>
            <x-button.secondary type="button" x-on:click="breakUp()" class="py-2">{{ __('Break it into steps') }}</x-button.secondary>
          </div>
        </template>

        <template x-if="steps.length > 1">
          <div class="space-y-3">
            <ol class="overflow-hidden rounded-xl border border-hairline">
              <template x-for="(step, index) in steps" :key="index">
                <li class="space-y-2 border-b border-hairline-soft py-3 pr-2.5 pl-3">
                  <div class="flex items-center gap-2">
                    <span class="flex size-5.5 shrink-0 items-center justify-center rounded-md text-xs font-bold" x-bind:class="step.requires_staff ? 'bg-ink text-canvas' : 'bg-canvas text-muted ring-[1.5px] ring-hairline-strong ring-inset'" x-text="index + 1" aria-hidden="true"></span>
                    @foreach ($viewModel->languages() as $code)
                      <input
                        type="text"
                        x-bind:name="`steps[${index}][name][{{ $code }}]`"
                        x-model="step.name[@js($code)]"
                        x-show="language === @js($code)"
                        maxlength="255"
                        aria-label="{{ __('Step name') }}"
                        placeholder="{{ __('Name this step') }}"
                        class="min-w-0 flex-1 rounded-lg border border-transparent bg-transparent px-1.5 py-1.25 text-[15px] font-semibold text-ink placeholder:font-normal placeholder:text-muted hover:border-hairline-strong focus:border-focus focus:bg-canvas focus:outline-none"
                      />
                    @endforeach
                    <label class="flex h-8 shrink-0 cursor-text items-center gap-1 rounded-[10px] border border-hairline bg-input px-2 focus-within:ring-2 focus-within:ring-focus-ring hover:border-focus">
                      <span class="sr-only">{{ __('Step duration in minutes') }}</span>
                      <input type="text" inputmode="numeric" maxlength="4" x-bind:name="`steps[${index}][duration]`" x-model="step.duration" class="w-8 border-0 bg-transparent p-0 text-right text-sm text-ink tabular-nums focus:outline-none" />
                      <span class="text-[13px] text-muted" aria-hidden="true">{{ __('min') }}</span>
                    </label>
                  </div>
                  <div class="flex items-center justify-between gap-2 pl-7.5">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                      <input type="hidden" x-bind:name="`steps[${index}][requires_staff]`" value="0" />
                      <input type="checkbox" value="1" x-bind:name="`steps[${index}][requires_staff]`" x-model="step.requires_staff" x-bind:disabled="isLastBusyStep(index)" class="size-4 cursor-pointer rounded accent-ink disabled:cursor-default" />
                      {{ __('Needs a team member') }}
                    </label>
                    <input type="hidden" x-bind:name="`steps[${index}][requires_staff]`" value="1" x-bind:disabled="! isLastBusyStep(index)" />
                    <div class="flex">
                      <button type="button" class="{{ $iconButton }}" x-on:click="move(index, -1)" x-bind:disabled="index === 0" aria-label="{{ __('Move this step up') }}">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 13V3M4 7l4-4 4 4"></path></svg>
                      </button>
                      <button type="button" class="{{ $iconButton }}" x-on:click="move(index, 1)" x-bind:disabled="index === steps.length - 1" aria-label="{{ __('Move this step down') }}">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3v10M4 9l4 4 4-4"></path></svg>
                      </button>
                      <button type="button" class="{{ $iconButton }}" x-on:click="remove(index)" aria-label="{{ __('Remove this step') }}">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line><line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line></svg>
                      </button>
                    </div>
                  </div>
                </li>
              </template>
              <li>
                <button type="button" x-on:click="addStep()" class="flex w-full cursor-pointer items-center gap-2 p-3 text-left text-sm font-semibold text-body hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none focus-visible:ring-inset">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
                  {{ __('Add a step') }}
                </button>
              </li>
            </ol>
            <p class="text-xs leading-normal text-pretty text-muted">{{ __('The length of the appointment is the sum of its steps. During a step that needs nobody, the team member can be booked with another client.') }}</p>
          </div>
        </template>
      </div>
    </div>
  </section>

  <!-- salons and people -->
  <section class="space-y-3.5" aria-labelledby="service-offers-title">
    <div class="space-y-1">
      <h2 id="service-offers-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Where it is offered, and by whom') }}</h2>
      <p class="text-[15px] text-pretty text-body">{{ __('Pick the salons that offer it, then the people who do it in each one. A client can only book it with somebody ticked here.') }}</p>
    </div>

    @if ($viewModel->salons() === [])
      <p class="rounded-[18px] bg-canvas p-6 text-[15px] text-body ring-[1.5px] ring-hairline">{{ __('The company has no salon yet. Add one in the settings, then come back to offer the service there.') }}</p>
    @endif

    <div class="grid grid-cols-[repeat(auto-fill,minmax(min(300px,100%),1fr))] items-start gap-4">
      @foreach ($viewModel->salons() as $salon)
        <fieldset class="overflow-hidden rounded-[14px] bg-canvas ring-[1.5px] ring-hairline">
          <legend class="sr-only">{{ $salon['name'] }}</legend>
          <label class="flex cursor-pointer items-center gap-3 px-4 py-3.5">
            <input type="checkbox" x-model="salons[{{ $salon['id'] }}].on" class="size-4 cursor-pointer rounded accent-ink" />
            <input type="hidden" name="offers[{{ $salon['id'] }}][]" value="" x-bind:disabled="! salons[{{ $salon['id'] }}].on" />
            <span class="accent-tile size-3 shrink-0 rounded" style="--tile-hue: {{ $salon['hue'] }}" aria-hidden="true"></span>
            <span class="text-[15px] font-bold text-ink">{{ $salon['name'] }}</span>
            @if ($salon['isHeadquarters'])
              <span class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Head office') }}</span>
            @endif
            <span class="flex-1"></span>
            <span class="text-[13px] text-muted" x-show="! salons[{{ $salon['id'] }}].on">{{ __('Not offered here') }}</span>
            <span class="text-[13px] text-warning" x-show="salons[{{ $salon['id'] }}].on && salons[{{ $salon['id'] }}].people.length === 0" x-cloak>{{ __('Nobody yet') }}</span>
            <span class="text-[13px] text-muted tabular-nums" x-show="salons[{{ $salon['id'] }}].on && salons[{{ $salon['id'] }}].people.length > 0" x-cloak x-text="{{ Js::from(__(':picked of :total people')) }}.replace(':picked', salons[{{ $salon['id'] }}].people.length).replace(':total', {{ count($salon['people']) }})"></span>
          </label>

          <div class="space-y-2.5 border-t border-hairline-soft px-4 pt-3 pb-3.5" x-show="salons[{{ $salon['id'] }}].on" @if (! $salon['isOn']) x-cloak @endif>
            @if ($salon['people'] === [])
              <p class="px-2 text-sm text-muted">{{ __('Nobody works here yet.') }}</p>
            @else
              <div class="grid gap-0.5">
                @foreach ($salon['people'] as $person)
                  <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-hover">
                    <input type="checkbox" name="offers[{{ $salon['id'] }}][]" value="{{ $person['id'] }}" x-model.number="salons[{{ $salon['id'] }}].people" x-bind:disabled="! salons[{{ $salon['id'] }}].on" class="size-4 cursor-pointer rounded accent-ink" />
                    <x-avatar :staff="$person['staff']" :name="$person['name']" :size="26" />
                    <span class="text-sm text-ink">{{ $person['name'] }}</span>
                  </label>
                @endforeach
              </div>
              <button type="button" x-on:click="toggleEveryone({{ $salon['id'] }})" class="cursor-pointer px-2 text-sm text-body underline underline-offset-3 hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">
                <span x-show="! isEveryone({{ $salon['id'] }})">{{ __('Tick everyone at :salon', ['salon' => $salon['name']]) }}</span>
                <span x-show="isEveryone({{ $salon['id'] }})" x-cloak>{{ __('Untick everyone') }}</span>
              </button>
            @endif
          </div>
        </fieldset>
      @endforeach
    </div>
  </section>
</x-form>

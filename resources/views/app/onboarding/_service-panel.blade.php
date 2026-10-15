{{-- The panel that edits one service in full: its texts, its category, its prices and its steps. --}}
{{-- @var \App\ViewModels\Onboarding\ServicePanel $panel --}}
@php
  $close = "\$ajax('".route('onboarding.services.index')."', { target: 'service-panel catalogue-footer' })";
  $iconButton = 'flex size-7 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none disabled:cursor-default disabled:opacity-35 disabled:hover:bg-transparent';
  $first = $panel->firstLanguage();
@endphp

<x-side-modal show="true" :close="$close" labelledby="service-panel-title">
  <x-slot:header>
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ $panel->eyebrow() }}</p>
    <h2 id="service-panel-title" class="mt-1.5 text-[26px] font-bold tracking-[-0.025em] wrap-anywhere text-ink">{{ $panel->title() }}</h2>
  </x-slot>

  <div
    class="space-y-5"
    x-data="{
      steps: @js($panel->steps()),
      names: @js($panel->names()),
      processingName: @js($panel->processingName()),
      total() { return this.steps.reduce((sum, step) => sum + (Number(step.duration) || 0), 0) },
      busy() { return this.steps.filter((step) => step.requires_staff).reduce((sum, step) => sum + (Number(step.duration) || 0), 0) },
      submit() { this.$nextTick(() => document.getElementById('service-panel-form').requestSubmit()) },
      breakUp() {
        this.steps = [
          { name: { ...this.names }, duration: Number(this.steps[0].duration) || 30, requires_staff: true },
          { name: { ...this.processingName }, duration: 15, requires_staff: false },
        ]
        this.submit()
      },
      addStep() {
        this.steps.push({ name: {}, duration: 15, requires_staff: true })
        this.submit()
      },
      move(index, offset) {
        const steps = [...this.steps];
        [steps[index], steps[index + offset]] = [steps[index + offset], steps[index]]
        this.steps = steps
        this.submit()
      },
      remove(index) {
        if (this.steps.length === 2) {
          this.steps = [{ name: {}, duration: this.steps[1 - index].duration, requires_staff: true }]
        } else {
          this.steps.splice(index, 1)
        }
        this.submit()
      },
      isLastBusyStep(index) {
        return this.steps[index].requires_staff && this.steps.filter((step) => step.requires_staff).length === 1
      },
    }"
  >
    <x-form
      method="put"
      :action="$panel->updateUrl()"
      id="service-panel-form"
      x-target="catalogue catalogue-footer service-panel"
      x-on:change="$el.requestSubmit()"
      class="space-y-5"
    >
      @if ($errors->any())
        <ul role="alert" class="space-y-1 rounded-xl bg-error/8 px-3.5 py-3 text-sm text-error ring-[1.5px] ring-error/25">
          @foreach ($errors->all() as $message)
            <li>{{ $message }}</li>
          @endforeach
        </ul>
      @endif

      <!-- panel language picker -->
      @if (count($panel->names()) > 1)
        <div class="space-y-2 rounded-xl bg-page p-3">
          <div class="flex justify-between gap-2 text-xs text-muted">
            <span class="font-bold tracking-[0.08em] uppercase">{{ __('Language') }}</span>
            @foreach ($panel->translationStatus() as $code => $status)
              <span x-show="language === @js($code)" @if (! $loop->first) x-cloak @endif>{{ $status }}</span>
            @endforeach
          </div>
          <div class="flex flex-wrap gap-1.5">
            @foreach ($panel->names() as $code => $name)
              <button
                type="button"
                x-on:click="language = @js($code)"
                x-bind:aria-pressed="language === @js($code)"
                class="flex h-8 cursor-pointer items-center gap-1.75 rounded-lg border border-hairline bg-transparent px-2.5 aria-pressed:border-ink aria-pressed:bg-canvas text-sm font-semibold text-ink hover:border-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
              >
                <span class="block h-3 w-4.5 rounded-xs ring-1 ring-black/10" style="background: {{ config("jacques.content_languages.{$code}.flag") }}" aria-hidden="true"></span>
                {{ config("jacques.content_languages.{$code}.label", $code) }}
              </button>
            @endforeach
          </div>
        </div>
      @endif

      <!-- service texts -->
      @foreach ($panel->names() as $code => $name)
        <div class="space-y-1.5" x-show="language === @js($code)" @if (! $loop->first) x-cloak @endif>
          <x-label for="service-name-{{ $code }}">{{ __('Name (:language)', ['language' => config("jacques.content_languages.{$code}.label", $code)]) }}</x-label>
          <input
            id="service-name-{{ $code }}"
            type="text"
            name="name[{{ $code }}]"
            x-model="names[@js($code)]"
            maxlength="255"
            placeholder="{{ $code === $first ? __('Name this service') : ($panel->names()[$first] ?: __('Name this service')) }}"
            class="block w-full rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink transition-colors placeholder:text-placeholder hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
          />
          @if ($code !== $first && $panel->names()[$first] !== '')
            <p class="text-xs text-muted">{{ config("jacques.content_languages.{$first}.label", $first) }}: {{ $panel->names()[$first] }}</p>
          @endif
        </div>
      @endforeach

      @foreach ($panel->descriptions() as $code => $description)
        <div class="space-y-1.5" x-show="language === @js($code)" @if (! $loop->first) x-cloak @endif>
          <x-label for="service-description-{{ $code }}">{{ __('Description (:language)', ['language' => config("jacques.content_languages.{$code}.label", $code)]) }}</x-label>
          <textarea
            id="service-description-{{ $code }}"
            name="description[{{ $code }}]"
            rows="2"
            maxlength="1000"
            placeholder="{{ __('Shown to clients when they book. Optional.') }}"
            class="block w-full resize-y rounded-[10px] border-[1.5px] border-control bg-input px-3 py-2.5 text-base leading-snug text-ink placeholder:text-placeholder hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
          >{{ $description }}</textarea>
        </div>
      @endforeach

      <div class="space-y-1.5">
        <x-label for="service-category">{{ __('Category') }}</x-label>
        <select id="service-category" name="category" class="block w-full cursor-pointer rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none">
          <option value="" @selected($panel->categoryId() === null)>{{ __('Without a category') }}</option>
          @foreach ($panel->categoryOptions() as $option)
            <option value="{{ $option['id'] }}" @selected($option['id'] === $panel->categoryId())>{{ $option['label'] }}</option>
          @endforeach
        </select>
      </div>

      <!-- service prices -->
      <fieldset class="space-y-2">
        <div class="flex items-baseline justify-between gap-2">
          <legend class="text-sm font-semibold text-ink">{{ __('Price') }}</legend>
          <span class="text-xs font-semibold {{ $panel->hasEveryPrice() ? 'text-success' : 'text-warning' }}">{{ $panel->priceStatus() }}</span>
        </div>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(120px,1fr))] gap-2">
          @foreach ($panel->prices() as $price)
            <label class="flex cursor-text flex-col gap-0.5 rounded-[10px] border px-3 py-2 transition-colors hover:border-focus focus-within:ring-2 focus-within:ring-focus-ring {{ $price['isMissing'] ? 'border-dashed border-warning bg-warning/10' : 'border-hairline-strong bg-input' }}">
              <span class="text-xs text-muted">{{ $price['name'] }}</span>
              <span class="flex items-baseline gap-1">
                <span class="text-[15px] text-muted" aria-hidden="true">{{ $price['symbol'] }}</span>
                <input type="text" inputmode="decimal" name="prices[{{ $price['code'] }}]" value="{{ $price['amount'] }}" placeholder="{{ __('Add a price') }}" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base font-semibold text-ink tabular-nums placeholder:font-normal placeholder:text-muted focus:outline-none" />
              </span>
            </label>
          @endforeach
        </div>
        <p class="text-xs leading-normal text-pretty text-muted">{{ __('Clients pay in the currency they book in. Enter 0 for a free service.') }}</p>
      </fieldset>

      <!-- duration and steps -->
      <div class="space-y-3.5 border-t border-hairline-soft pt-5">
        <div class="flex items-baseline justify-between gap-3">
          <h3 class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Duration and steps') }}</h3>
          <p class="text-sm font-semibold text-ink tabular-nums"><span x-text="total()"></span> {{ __('min') }}</p>
        </div>

        <template x-if="steps.length === 1">
          <div class="space-y-3.5">
            <div class="space-y-1.5">
              <x-label for="service-duration">{{ __('Duration (minutes)') }}</x-label>
              <input id="service-duration" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" x-on:beforeinput="if (/\D/.test($event.data ?? $event.dataTransfer?.getData('text') ?? '')) $event.preventDefault()" name="steps[0][duration]" x-model="steps[0].duration" class="block w-full rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink tabular-nums hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none" />
              <input type="hidden" name="steps[0][requires_staff]" value="1" />
            </div>
            <p class="text-sm leading-normal text-pretty text-body">{{ __('Some services include processing time when your team member can attend to another client. Break a service into steps to manage availability more accurately.') }}</p>
            <x-button.secondary type="button" x-on:click="breakUp()" class="py-2">{{ __('Break it into steps') }}</x-button.secondary>
          </div>
        </template>

        <template x-if="steps.length > 1">
          <div class="space-y-4">
            <div class="space-y-2 rounded-xl bg-page p-3.5">
              <div class="grid grid-cols-[74px_minmax(0,1fr)] items-center gap-2" aria-hidden="true">
                <span class="text-xs text-muted">{{ __('Client') }}</span>
                <div class="flex h-5.5 gap-0.75">
                  <template x-for="(step, index) in steps" :key="index">
                    <span class="min-w-0 rounded-[5px] bg-hairline" x-bind:style="`flex: ${Math.max(Number(step.duration) || 0, 1)}`"></span>
                  </template>
                </div>
                <span class="text-xs text-muted">{{ __('Team member') }}</span>
                <div class="flex h-5.5 gap-0.75">
                  <template x-for="(step, index) in steps" :key="index">
                    <span class="min-w-0 rounded-[5px]" x-bind:class="step.requires_staff ? 'bg-ink' : 'bg-canvas ring-[1.5px] ring-hairline-strong ring-inset'" x-bind:style="`flex: ${Math.max(Number(step.duration) || 0, 1)}`"></span>
                  </template>
                </div>
              </div>
              <dl class="grid grid-cols-3 gap-2 border-t border-hairline pt-2">
                <div class="space-y-0.5"><dt class="text-xs text-muted">{{ __('Appointment') }}</dt><dd class="text-[15px] font-bold tabular-nums"><span x-text="total()"></span> {{ __('min') }}</dd></div>
                <div class="space-y-0.5"><dt class="text-xs text-muted">{{ __('Team member busy') }}</dt><dd class="text-[15px] font-bold tabular-nums"><span x-text="busy()"></span> {{ __('min') }}</dd></div>
                <div class="space-y-0.5"><dt class="text-xs text-muted">{{ __('Free for others') }}</dt><dd class="text-[15px] font-bold tabular-nums"><span x-text="total() - busy()"></span> {{ __('min') }}</dd></div>
              </dl>
            </div>

            <ol class="overflow-hidden rounded-xl border border-hairline">
              <template x-for="(step, index) in steps" :key="index">
                <li class="space-y-2 border-b border-hairline-soft py-3 pr-2.5 pl-3">
                  <div class="flex items-center gap-2">
                    <span class="flex size-5.5 shrink-0 items-center justify-center rounded-md text-xs font-bold" x-bind:class="step.requires_staff ? 'bg-ink text-canvas' : 'bg-canvas text-muted ring-[1.5px] ring-hairline-strong ring-inset'" x-text="index + 1" aria-hidden="true"></span>
                    @foreach ($panel->names() as $code => $name)
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
                    <label class="flex h-8 shrink-0 cursor-text items-center gap-1 rounded-[10px] border border-hairline bg-input px-2 hover:border-focus focus-within:ring-2 focus-within:ring-focus-ring">
                      <span class="sr-only">{{ __('Step duration in minutes') }}</span>
                      <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" x-on:beforeinput="if (/\D/.test($event.data ?? $event.dataTransfer?.getData('text') ?? '')) $event.preventDefault()" x-bind:name="`steps[${index}][duration]`" x-model="step.duration" class="w-7 border-0 bg-transparent p-0 text-right text-sm text-ink tabular-nums focus:outline-none" />
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
    </x-form>

    <div class="flex items-center justify-between gap-2.5 border-t border-hairline-soft pt-5">
      <x-form method="delete" :action="$panel->destroyUrl()" x-target="catalogue catalogue-footer service-panel">
        <button type="submit" class="cursor-pointer py-2 text-sm text-error hover:underline">{{ __('Remove this service') }}</button>
      </x-form>
      <x-button type="button" x-on:click="{{ $close }}">{{ __('Done') }}</x-button>
    </div>
  </div>
</x-side-modal>

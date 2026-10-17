{{-- The panel giving one team member a week of their own, compared with the opening hours on the screen. --}}
{{-- @var \App\ViewModels\Onboarding\StaffHoursPanel $panel --}}
@php
  $close = "\$ajax('".route('onboarding.availability.index')."', { target: 'hours-panel' })";
  $box = 'flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-control bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring';
  $state = 'workingHoursPanel({ times, sentences, locale, days: '.Js::from($panel->week()).', follows: '.Js::from($panel->follows()).' })';
  $select = 'block w-27 cursor-pointer rounded-[10px] border-[1.5px] border-control bg-input px-3 py-2.5 text-base text-ink tabular-nums hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none';
@endphp

<x-side-modal show="true" :close="$close" labelledby="hours-panel-title" :x-data="$state" x-init="start(days)">
  <x-slot:header>
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Working hours') }}</p>
    <h2 id="hours-panel-title" class="mt-1.5 text-[26px] font-bold tracking-[-0.025em] wrap-anywhere text-ink">{{ $panel->name() }}</h2>
  </x-slot>

  <x-form method="put" :action="$panel->formAction()" id="hours-panel-form" x-target="team-hours hours-panel availability-footer" class="space-y-4.5">
    <!-- weekly total -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <span class="text-sm font-semibold text-ink tabular-nums" x-text="summary(member)"></span>
      <button type="button" x-on:click="restart(days)" class="cursor-pointer py-1 text-sm text-body underline underline-offset-3 hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">{{ __('Start again from salon hours') }}</button>
    </div>

    <x-error :messages="$errors->get('days')" />

    <!-- the week -->
    <div class="overflow-hidden rounded-xl ring-1 ring-hairline">
      <template x-for="(day, index) in member" :key="day.day">
        <div class="space-y-2.5 border-b border-hairline-soft p-3.5 last:border-b-0">
          <div class="flex items-center justify-between gap-2.5">
            <label class="flex cursor-pointer items-center gap-2.5" :for="`work-${day.day}`">
              <input type="checkbox" class="peer sr-only" :id="`work-${day.day}`" x-model="day.open" />
              <span class="{{ $box }}" aria-hidden="true">
                <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path></svg>
              </span>
              <span class="text-[15px] font-bold text-ink" x-text="day.name"></span>
            </label>
            <span class="text-xs text-muted" x-text="salonText(days[index])"></span>
          </div>

          <div x-show="day.open" class="space-y-2 pl-6.5">
            <template x-for="(range, rangeIndex) in day.ranges">
              <div class="flex flex-wrap items-center gap-2.5">
                <select class="{{ $select }}" x-model="range.start" :aria-label="@js(__('Start on :day')).replace(':day', day.name)">
                  <template x-for="option in times" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === range.start"></option>
                  </template>
                </select>
                <span class="text-sm text-muted">{{ __('to') }}</span>
                <select class="{{ $select }}" x-model="range.end" :aria-label="@js(__('End on :day')).replace(':day', day.name)">
                  <template x-for="option in times" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === range.end"></option>
                  </template>
                </select>
                <button
                  type="button"
                  x-show="day.ranges.length > 1"
                  x-on:click="day.ranges.splice(rangeIndex, 1)"
                  aria-label="{{ __('Remove these hours') }}"
                  class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
                >
                  <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line><line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line></svg>
                </button>
              </div>
            </template>

            <p x-show="dayError(day)" class="text-sm text-error" role="alert" x-text="dayError(day)"></p>
            <p x-show="warning(day, days[index])" class="text-sm text-warning" x-text="warning(day, days[index])"></p>

            <button type="button" x-show="canAddRange(day)" x-on:click="addHours(day)" class="flex cursor-pointer items-center gap-1.5 py-1 text-sm font-semibold text-body transition-colors hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">
              <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
              {{ __('Add hours') }}
            </button>
          </div>

          <input type="hidden" :name="`days[${day.day}][open]`" :value="day.open ? 1 : 0" />
          <template x-for="(range, rangeIndex) in day.ranges">
            <div>
              <input type="hidden" :name="`days[${day.day}][ranges][${rangeIndex}][start]`" :value="range.start" />
              <input type="hidden" :name="`days[${day.day}][ranges][${rangeIndex}][end]`" :value="range.end" />
            </div>
          </template>
        </div>
      </template>
    </div>

    <div class="flex justify-end gap-2.5 pt-1">
      <x-button.secondary type="button" x-on:click="{{ $close }}">{{ __('Cancel') }}</x-button.secondary>
      <x-button x-bind:disabled="hasErrors(member)">{{ __('Save hours') }}</x-button>
    </div>
  </x-form>
</x-side-modal>

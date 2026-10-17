{{-- The week the salon is open, edited in the browser and stored when the step is finished. --}}
{{-- @var \App\ViewModels\Onboarding\AvailabilityViewModel $viewModel --}}
@php
  $box = 'flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-control bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring';
  $select = 'block w-27 cursor-pointer rounded-[10px] border-[1.5px] border-control bg-input px-3 py-2.5 text-base text-ink tabular-nums hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none';
  $textButton = 'flex cursor-pointer items-center gap-1.5 py-1 text-sm font-semibold text-body transition-colors hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none';
@endphp

<!-- opening hours -->
<section class="space-y-4" aria-labelledby="opening-hours-title">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div class="max-w-160 space-y-1">
      <h2 id="opening-hours-title" class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Opening hours') }}</h2>
      <p class="text-sm leading-normal text-pretty text-body">{{ $viewModel->openingHoursHelp() }}</p>
    </div>
    <p class="text-sm font-semibold text-ink tabular-nums" x-text="summary(days)"></p>
  </div>

  <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
    <div class="px-4.5 pt-4.5 pb-1 sm:px-6">
      <!-- hour ticks -->
      <div class="hidden grid-cols-[150px_56px_minmax(0,1fr)_120px] items-end gap-5 border-b border-hairline-soft pb-3 md:grid" aria-hidden="true">
        <span></span>
        <span></span>
        <div class="relative h-4.5">
          @foreach ([6, 8, 10, 12, 14, 16, 18, 20, 22] as $hour)
            <span class="absolute -translate-x-1/2 text-xs whitespace-nowrap text-muted tabular-nums" style="left: {{ ($hour - 6) / 16 * 100 }}%">{{ $hour }}:00</span>
          @endforeach
        </div>
        <span></span>
      </div>

      <template x-for="(day, index) in days" :key="day.day">
        <div class="border-b border-hairline-soft">
          <!-- one day -->
          <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-5 gap-y-2.5 py-3 md:grid-cols-[150px_56px_minmax(0,1fr)_120px]">
            <label class="flex cursor-pointer items-center gap-2.5" :for="`open-${day.day}`">
              <input type="checkbox" class="peer sr-only" :id="`open-${day.day}`" x-model="day.open" x-on:change="toggle(index)" :aria-label="@js(__('Open on :day')).replace(':day', day.name)" />
              <span class="{{ $box }}" aria-hidden="true">
                <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path></svg>
              </span>
              <span class="text-[17px] font-bold tracking-[-0.025em]" :class="day.open ? 'text-ink' : 'text-muted'" x-text="day.short"></span>
            </label>

            <span class="text-right text-[22px] font-bold tracking-[-0.025em] tabular-nums max-md:text-base" :class="day.open ? 'text-ink' : 'text-muted'" x-text="dayHours(day)"></span>

            <div
              class="relative col-span-2 h-10 rounded-[10px] bg-page md:col-span-1"
              :class="[dayError(day) ? 'ring-[1.5px] ring-error ring-inset' : 'ring-1 ring-hairline-soft ring-inset', day.open ? 'cursor-pointer' : '']"
              x-on:click="edit(index)"
              aria-hidden="true"
            >
              <template x-if="day.open">
                <div>
                  <template x-for="bar in bars(day)">
                    <div class="accent-tile absolute inset-y-0 flex items-center justify-between gap-1.5 overflow-hidden rounded-[10px] border-[1.5px] border-current/35 px-1.5 text-sm font-bold whitespace-nowrap tabular-nums transition-[left,width] duration-150" style="--tile-hue: 80" :style="{ left: bar.left, width: bar.width }">
                      <template x-if="bar.isWide">
                        <span class="flex w-full justify-between"><span x-text="bar.start"></span><span x-text="bar.end"></span></span>
                      </template>
                      <template x-if="! bar.isWide">
                        <span class="flex-1 truncate text-center text-[13px]" x-text="bar.compact"></span>
                      </template>
                    </div>
                  </template>
                </div>
              </template>
              <span x-show="! day.open" class="absolute top-1/2 left-3.5 -translate-y-1/2 text-sm text-muted">{{ __('Closed') }}</span>
            </div>

            <div class="col-span-2 flex justify-end md:col-span-1">
              <x-button.secondary type="button" x-show="day.open" x-on:click="edit(index)" class="py-2" x-bind:aria-expanded="editing === index">
                <span x-show="editing !== index">{{ __('Edit hours') }}</span>
                <span x-show="editing === index" x-cloak>{{ __('Close') }}</span>
                <span class="sr-only" x-text="day.name"></span>
              </x-button.secondary>
            </div>
          </div>

          <p x-show="dayError(day) && editing !== index" x-cloak class="pb-3 text-sm text-error" role="alert" x-text="dayError(day)"></p>

          <!-- hours of one day -->
          <div x-show="day.open && editing === index" x-cloak class="mb-4 space-y-2.5 rounded-xl bg-canvas px-4 py-3.5 ring-1 ring-hairline md:ml-61.5">
            <template x-for="(range, rangeIndex) in day.ranges">
              <div class="flex flex-wrap items-center gap-2.5">
                <select class="{{ $select }}" x-model="range.start" :aria-label="@js(__('Opening on :day')).replace(':day', day.name)">
                  <template x-for="option in times" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === range.start"></option>
                  </template>
                </select>
                <span class="text-sm text-muted">{{ __('to') }}</span>
                <select class="{{ $select }}" x-model="range.end" :aria-label="@js(__('Closing on :day')).replace(':day', day.name)">
                  <template x-for="option in times" :key="option.value">
                    <option :value="option.value" x-text="option.label" :selected="option.value === range.end"></option>
                  </template>
                </select>
                <button
                  type="button"
                  x-show="day.ranges.length > 1"
                  x-on:click="removeRange(day, rangeIndex)"
                  aria-label="{{ __('Remove these hours') }}"
                  class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
                >
                  <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line><line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line></svg>
                </button>
              </div>
            </template>

            <p x-show="dayError(day)" class="text-sm text-error" role="alert" x-text="dayError(day)"></p>

            <div class="flex flex-wrap items-center gap-x-4.5 gap-y-1">
              <button type="button" x-show="canAddRange(day)" x-on:click="addRange(day)" class="{{ $textButton }}">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
                {{ __('Add opening hours') }}
              </button>
              <button type="button" x-show="lunchIndex(day) >= 0" x-on:click="closeForLunch(day)" class="{{ $textButton }}">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 3v10M3 8h10"></path></svg>
                {{ __('Close for lunch, 12:00 to 13:00') }}
              </button>
              <span class="flex-1"></span>
              <button type="button" x-on:click="editing = null" class="cursor-pointer py-1 text-sm text-body underline underline-offset-3 hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">{{ __('Done') }}</button>
            </div>
          </div>
        </div>
      </template>

      <p class="py-3.5 text-sm text-muted">{{ __('A day can have several opening hours, for example when the salon closes for lunch.') }}</p>
    </div>
  </div>

  <x-form method="put" :action="route('onboarding.availability.update')" id="availability-form">
    <input type="hidden" name="use_opening_hours" :value="useOpeningHours ? 1 : 0" />
    <template x-for="day in days" :key="day.day">
      <div>
        <input type="hidden" :name="`days[${day.day}][open]`" :value="day.open ? 1 : 0" />
        <template x-for="(range, rangeIndex) in day.ranges">
          <div>
            <input type="hidden" :name="`days[${day.day}][ranges][${rangeIndex}][start]`" :value="range.start" />
            <input type="hidden" :name="`days[${day.day}][ranges][${rangeIndex}][end]`" :value="range.end" />
          </div>
        </template>
      </div>
    </template>
  </x-form>
</section>

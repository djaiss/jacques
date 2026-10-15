{{-- The first onboarding step, where a new salon gets its name, its address and its time zone. --}}
{{-- @var \App\ViewModels\Onboarding\LocationViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $editing = ! $viewModel->isSetUp() || $errors->any();
  $selectClasses = 'block w-full cursor-pointer appearance-none rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink transition-colors duration-150 hover:border-ink hover:bg-hover focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none disabled:cursor-default disabled:opacity-60 aria-invalid:border-error';
@endphp

<x-top-bar-layout :title="__('Set up your salon')">
  @include('app.onboarding._header', ['viewModel' => $viewModel])

  <div
    class="mt-7"
    x-data="{
      editing: @js($editing),
      country: @js($form['country']),
      timezone: @js($form['timezone']),
      timezones: @js($viewModel->timezonesByCountry()),
      chooseCountry() {
        this.timezone = this.timezones[this.country]?.[0]?.value ?? ''
      },
    }"
  >
    <!-- confirmation -->
    <div x-show="! editing" @if ($editing) x-cloak @endif class="max-w-180 space-y-4.5 rounded-[18px] bg-canvas p-7 ring-[1.5px] ring-hairline">
      <div class="space-y-1.5">
        <h2 class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __(':name is ready', ['name' => $viewModel->salonName()]) }}</h2>
        <p class="text-sm leading-normal text-pretty text-body">{{ __('This is your first location. You can add more later from Settings. Next, list the services your salon offers.') }}</p>
      </div>

      <div class="flex flex-wrap gap-2.5">
        <x-button :href="route('onboarding.services.index')">{{ __('Continue to services') }}</x-button>
        <x-button.secondary type="button" @click="editing = true">{{ __('Edit the salon') }}</x-button.secondary>
      </div>
    </div>

    <!-- salon form -->
    <div x-show="editing" @unless ($editing) x-cloak @endunless class="max-w-180 rounded-[18px] bg-canvas p-7 ring-[1.5px] ring-hairline">
      <x-form method="put" :action="route('onboarding.location.update')" novalidate class="space-y-5">
        <div class="flex items-start gap-3.5 border-b border-hairline-soft pb-5.5">
          <span class="accent-tile flex size-9.5 shrink-0 items-center justify-center rounded-xl" style="--tile-hue: 30" aria-hidden="true">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 9.5V20h16V9.5" />
              <path d="M3 9.5l1.6-5.5h14.8L21 9.5" />
              <path d="M3 9.5a3 3 0 0 0 6 0a3 3 0 0 0 6 0a3 3 0 0 0 6 0" />
              <path d="M9.5 20v-5h5v5" />
            </svg>
          </span>

          <div class="space-y-1">
            <h2 class="text-[22px] font-bold tracking-[-0.025em] text-ink">{{ __('Set up your salon') }}</h2>
            <p class="text-sm text-body">{{ __('Tell us a little about where you work.') }}</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <x-label for="name">{{ __('Salon name') }} <span class="text-error" aria-hidden="true">*</span></x-label>
          <x-input id="name" :value="$form['name']" :error="$errors->get('name')" :required="true" autocomplete="organization" maxlength="255" />
        </div>

        <x-input id="address_line_1" :label="__('Street address')" :value="$form['addressLine1']" :error="$errors->get('address_line_1')" autocomplete="address-line1" maxlength="255" />

        <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
          <x-input id="city" :label="__('City')" :value="$form['city']" :error="$errors->get('city')" autocomplete="address-level2" maxlength="255" />
          <x-input id="region" :label="__('Province / State')" :value="$form['region']" :error="$errors->get('region')" autocomplete="address-level1" maxlength="255" />
          <x-input id="postal_code" :label="__('Postal code')" :value="$form['postalCode']" :error="$errors->get('postal_code')" autocomplete="postal-code" maxlength="20" />

          <div class="space-y-1.5">
            <x-label for="country">{{ __('Country') }} <span class="text-error" aria-hidden="true">*</span></x-label>
            <select
              id="country"
              name="country"
              x-model="country"
              @change="chooseCountry()"
              required
              autocomplete="country"
              @if ($errors->has('country')) aria-invalid="true" aria-describedby="country-error" @endif
              class="{{ $selectClasses }}"
            >
              <option value="">{{ __('Choose a country') }}</option>
              @foreach ($viewModel->countries() as $code => $country)
                <option value="{{ $code }}">{{ $country }}</option>
              @endforeach
            </select>
            <x-error id="country-error" :messages="$errors->get('country')" />
          </div>
        </div>

        <div class="grid items-start gap-x-4 gap-y-5 sm:grid-cols-2">
          <x-input id="phone" type="tel" :label="__('Phone number')" :value="$form['phone']" :error="$errors->get('phone')" autocomplete="tel" maxlength="50" />

          <div class="space-y-1.5">
            <x-label for="timezone">{{ __('Time zone') }} <span class="text-error" aria-hidden="true">*</span></x-label>
            <select
              id="timezone"
              name="timezone"
              x-model="timezone"
              x-bind:disabled="! country"
              required
              aria-describedby="timezone-help{{ $errors->has('timezone') ? ' timezone-error' : '' }}"
              @if ($errors->has('timezone')) aria-invalid="true" @endif
              class="{{ $selectClasses }}"
            >
              <option value="" x-bind:hidden="country">{{ __('Choose a country first') }}</option>
              <template x-for="option in timezones[country] ?? []" :key="option.value">
                <option :value="option.value" x-text="option.label" :selected="option.value === timezone"></option>
              </template>
            </select>
            <p id="timezone-help" class="text-xs text-muted">{{ __('Used to schedule appointments at the correct local time.') }}</p>
            <x-error id="timezone-error" :messages="$errors->get('timezone')" />
          </div>
        </div>

        <x-button class="w-full">
          {{ __('Create my salon') }}
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 8h10M9 4l4 4-4 4" />
          </svg>
        </x-button>
      </x-form>
    </div>
  </div>
</x-top-bar-layout>

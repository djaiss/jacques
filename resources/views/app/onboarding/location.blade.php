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
      languages: @js($form['languages']),
      languagesByCountry: @js($viewModel->languagesByCountry()),
      languageCounters: @js($viewModel->languageCounters()),
      languagesWithTexts: @js($viewModel->languagesWithTexts()),
      currencies: @js($form['currencies']),
      currenciesByCountry: @js($viewModel->currenciesByCountry()),
      currencyCounters: @js($viewModel->currencyCounters()),
      currenciesWithPrices: @js($viewModel->currenciesWithPrices()),
      confirmingRemoval: false,
      removalConfirmed: false,
      removedLanguages() {
        return this.languagesWithTexts.filter((language) => ! this.languages.includes(language.code))
      },
      removedCurrencies() {
        return this.currenciesWithPrices.filter((currency) => ! this.currencies.includes(currency.code))
      },
      submitSalon(event) {
        if (this.removalConfirmed || this.removedLanguages().length + this.removedCurrencies().length === 0) {
          return
        }

        event.preventDefault()
        this.confirmingRemoval = true
      },
      chooseCountry() {
        this.timezone = this.timezones[this.country]?.[0]?.value ?? ''
        this.languages = [...(this.languagesByCountry[this.country] ?? ['en'])]
        this.currencies = [this.currenciesByCountry[this.country] ?? 'CAD']
      },
      toggleLanguage(code) {
        if (! this.languages.includes(code)) {
          this.languages.push(code)

          return
        }

        if (this.languages.length > 1) {
          this.languages = this.languages.filter((language) => language !== code)
        }
      },
      toggleCurrency(code) {
        if (! this.currencies.includes(code)) {
          this.currencies.push(code)

          return
        }

        if (this.currencies.length > 1) {
          this.currencies = this.currencies.filter((currency) => currency !== code)
        }
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
      <x-form method="put" :action="route('onboarding.location.update')" novalidate class="space-y-5" x-ref="salonForm" @submit="submitSalon($event)">
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

        <!-- languages of the salon -->
        <div class="space-y-2">
          <div class="flex flex-wrap items-baseline justify-between gap-3">
            <span id="languages-label" class="block text-sm leading-tight font-semibold text-ink">{{ __('Languages spoken at the salon') }} <span class="text-error" aria-hidden="true">*</span></span>
            <span class="text-xs text-muted" aria-live="polite">
              <span x-text="languageCounters[languages.length]"></span><span x-show="languages.length === 1">. {{ __('Keep at least one.') }}</span>
            </span>
          </div>

          <div role="group" aria-labelledby="languages-label" aria-describedby="languages-help{{ $errors->has('languages') ? ' languages-error' : '' }}" class="flex flex-wrap gap-2">
            @foreach ($viewModel->languages() as $language)
              <button
                type="button"
                @click="toggleLanguage(@js($language['code']))"
                :aria-pressed="languages.includes(@js($language['code'])).toString()"
                class="flex h-9.5 cursor-pointer items-center gap-2 rounded-[10px] border border-hairline-strong bg-input pr-3.5 pl-2.5 text-[15px] text-ink transition-colors duration-150 hover:border-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none aria-pressed:border-ink aria-pressed:bg-ink aria-pressed:text-canvas motion-reduce:transition-none"
              >
                <span class="flex size-4 items-center justify-center rounded-sm" :class="languages.includes(@js($language['code'])) ? 'bg-canvas' : 'ring-[1.5px] ring-hairline-strong ring-inset'" aria-hidden="true">
                  <svg x-show="languages.includes(@js($language['code']))" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="text-ink">
                    <path d="M3.5 8.5l3 3 6-7" />
                  </svg>
                </span>
                <span class="block h-3.5 w-5.25 rounded-[3px] ring-1 ring-black/10" style="background: {{ $language['flag'] }}" aria-hidden="true"></span>
                <span lang="{{ $language['code'] }}">{{ $language['label'] }}</span>
              </button>
            @endforeach
          </div>

          <template x-for="code in languages" :key="code">
            <input type="hidden" name="languages[]" :value="code" />
          </template>

          <p id="languages-help" class="text-xs text-muted">{{ __('Clients can book in these languages, and your team can say which ones they speak.') }}</p>
          <x-error id="languages-error" :messages="$errors->get('languages')" />
        </div>

        <!-- currencies of the salon -->
        <div class="space-y-2">
          <div class="flex flex-wrap items-baseline justify-between gap-3">
            <span id="currencies-label" class="block text-sm leading-tight font-semibold text-ink">{{ __('Currencies accepted at the salon') }} <span class="text-error" aria-hidden="true">*</span></span>
            <span class="text-xs text-muted" aria-live="polite">
              <span x-text="currencyCounters[currencies.length]"></span><span x-show="currencies.length === 1">. {{ __('Keep at least one.') }}</span>
            </span>
          </div>

          <div role="group" aria-labelledby="currencies-label" aria-describedby="currencies-help{{ $errors->has('currencies') ? ' currencies-error' : '' }}" class="flex flex-wrap gap-2">
            @foreach ($viewModel->currencies() as $currency)
              <button
                type="button"
                @click="toggleCurrency(@js($currency['code']))"
                :aria-pressed="currencies.includes(@js($currency['code'])).toString()"
                class="flex h-9.5 cursor-pointer items-center gap-2 rounded-[10px] border border-hairline-strong bg-input pr-3.5 pl-2.5 text-[15px] text-ink transition-colors duration-150 hover:border-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none aria-pressed:border-ink aria-pressed:bg-ink aria-pressed:text-canvas motion-reduce:transition-none"
              >
                <span class="flex size-4 items-center justify-center rounded-sm" :class="currencies.includes(@js($currency['code'])) ? 'bg-canvas' : 'ring-[1.5px] ring-hairline-strong ring-inset'" aria-hidden="true">
                  <svg x-show="currencies.includes(@js($currency['code']))" width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="text-ink">
                    <path d="M3.5 8.5l3 3 6-7" />
                  </svg>
                </span>
                <span>{{ $currency['label'] }} · {{ $currency['code'] }}</span>
              </button>
            @endforeach
          </div>

          <template x-for="code in currencies" :key="code">
            <input type="hidden" name="currencies[]" :value="code" />
          </template>

          <p id="currencies-help" class="text-xs text-muted">{{ __('Every price in your catalogue is written in each of these currencies.') }}</p>
          <x-error id="currencies-error" :messages="$errors->get('currencies')" />
        </div>

        <x-button class="w-full">
          {{ __('Create my salon') }}
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 8h10M9 4l4 4-4 4" />
          </svg>
        </x-button>
      </x-form>
    </div>

    <x-confirm-dialog
      show="confirmingRemoval"
      close="confirmingRemoval = false"
      labelledby="remove-languages-title"
      :title="__('Change the salon?')"
      :cancel="__('Keep them')"
    >
      <span x-show="removedLanguages().length > 0">{{ __('Your catalogue has texts in:') }} <span class="font-semibold text-ink" x-text="removedLanguages().map((language) => language.label).join(', ')"></span>. {{ __('Their texts stay saved, and come back if you add the language again.') }}</span>
      <span x-show="removedCurrencies().length > 0">{{ __('Your catalogue has prices in:') }} <span class="font-semibold text-ink" x-text="removedCurrencies().map((currency) => currency.label).join(', ')"></span>. {{ __('Their prices stay saved, and come back if you add the currency again.') }}</span>

      <x-slot:actions>
        <x-button.danger type="button" x-on:click="removalConfirmed = true; confirmingRemoval = false; $refs.salonForm.requestSubmit()">{{ __('Change the salon') }}</x-button.danger>
      </x-slot:actions>
    </x-confirm-dialog>
  </div>
</x-top-bar-layout>

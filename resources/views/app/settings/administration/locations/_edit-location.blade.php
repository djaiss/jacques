{{-- The panel that edits one location. --}}
{{-- @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel --}}
<x-side-modal show="open !== null" close="close()" labelledby="location-panel-title">
  <x-slot:header>
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Edit location') }}</p>

    <h2 id="location-panel-title" class="mt-1 text-[26px] font-bold tracking-tight wrap-anywhere text-ink" x-text="form.name || @js(__('Edit location'))"></h2>
  </x-slot:header>

  <!-- the fields of the location -->
  <x-form
    method="put"
    x-bind:action="location?.updateUrl"
    id="location-form"
    x-target="locations-table locations-statistics locations-data location-errors"
    x-on:ajax:after="refresh()"
    class="space-y-4.5 transition-opacity [&[aria-busy]]:opacity-60"
  >
    <input type="hidden" name="location_id" x-bind:value="open" />

    <div id="location-errors">
      @if ($errors->getBag('default')->any())
        <ul role="alert" class="space-y-1 rounded-xl bg-error/8 px-3.5 py-3 text-sm text-error ring-[1.5px] ring-error/25">
          @foreach ($errors->getBag('default')->all() as $message)
            <li>{{ $message }}</li>
          @endforeach
        </ul>
      @endif
    </div>

    <x-input
      id="location_name"
      name="name"
      :error="$errors->get('name')"
      x-model="form.name"
      :label="__('Name')"
      minlength="2"
      maxlength="255"
      required
    />

    <x-input
      id="location_address_line_1"
      name="address_line_1"
      :error="$errors->get('address_line_1')"
      x-model="form.addressLine1"
      :label="__('Address')"
      :placeholder="__('Street and number')"
      maxlength="255"
    />

    <x-input
      id="location_address_line_2"
      name="address_line_2"
      :error="$errors->get('address_line_2')"
      x-model="form.addressLine2"
      :label="__('Address line 2')"
      :placeholder="__('Building, floor or suite')"
      maxlength="255"
    />

    <div class="grid gap-4.5 sm:grid-cols-2">
      <x-input
        id="location_postal_code"
      name="postal_code"
      :error="$errors->get('postal_code')"
        x-model="form.postalCode"
        :label="__('Postal code')"
        maxlength="20"
      />

      <x-input
        id="location_city"
      name="city"
      :error="$errors->get('city')"
        x-model="form.city"
        :label="__('City')"
        maxlength="255"
      />
    </div>

    <div class="grid gap-4.5 sm:grid-cols-2">
      <x-input
        id="location_region"
      name="region"
      :error="$errors->get('region')"
        x-model="form.region"
        :label="__('Region')"
        :help="__('State, province or county')"
        maxlength="255"
      />

      <x-input
        id="location_country"
      name="country"
      :error="$errors->get('country')"
        x-model="form.country"
        :label="__('Country')"
        :help="__('Two letters, such as US')"
        maxlength="2"
        pattern="[A-Za-z]{2}"
        class="uppercase"
      />
    </div>

    <div class="grid gap-4.5 sm:grid-cols-2">
      <x-input
        id="location_phone"
      name="phone"
      :error="$errors->get('phone')"
        type="tel"
        x-model="form.phone"
        :label="__('Phone')"
        autocomplete="tel"
        maxlength="50"
      />

      <x-input
        id="location_email"
      name="email"
      :error="$errors->get('email')"
        type="email"
        x-model="form.email"
        :label="__('Email address')"
        autocomplete="email"
        maxlength="255"
      />
    </div>

    <div class="space-y-1.5">
      <x-label for="location_timezone" :value="__('Time zone')" />

      <select
        id="location_timezone"
        name="timezone"
        x-model="form.timezone"
        aria-describedby="location_timezone-help"
        class="block w-full appearance-none rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink transition-colors duration-150 hover:border-ink hover:bg-hover focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
      >
        <option value="">{{ __('Same as the company') }}</option>

        @foreach ($viewModel->timezones() as $timezone)
          <option value="{{ $timezone }}">{{ $timezone }}</option>
        @endforeach
      </select>

      <p id="location_timezone-help" class="text-xs text-muted">{{ __('Left empty, this location follows the company time zone.') }}</p>
    </div>

    <label
      class="flex cursor-pointer items-start gap-3.25 rounded-[14px] px-4 py-3.75 transition-colors"
      :class="form.isHeadquarters ? 'bg-accent-soft ring-[1.5px] ring-accent-ink/30' : 'bg-sunken ring-[1.5px] ring-hairline'"
    >
      <input type="checkbox" id="location_is_headquarters" name="is_headquarters" value="1" x-model="form.isHeadquarters" class="peer sr-only" />

      <span
        aria-hidden="true"
        class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-control bg-canvas text-transparent transition-colors peer-checked:border-accent-ink peer-checked:bg-accent-ink peer-checked:text-canvas peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring"
      >
        <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
        </svg>
      </span>

      <span>
        <span class="block text-base font-semibold text-ink">{{ __('Headquarters') }}</span>

        <span class="mt-1 block text-sm leading-relaxed text-body">
          {{ __('A company keeps one headquarters. Ticking this takes the badge off whichever location had it, and the only way to remove it is to give it to another location.') }}
        </span>
      </span>
    </label>

    <p class="rounded-[14px] bg-sunken px-4 py-3.25 text-sm leading-relaxed text-muted ring-[1.5px] ring-hairline" x-text="location?.inheritNote"></p>
  </x-form>

  <!-- save, reopen and archive -->
  <div class="mt-5 flex flex-wrap items-center gap-2.5">
    <x-button form="location-form">{{ __('Save changes') }}</x-button>

    <template x-if="location?.isArchived">
      <x-form
        method="delete"
        x-bind:action="location?.restoreUrl"
        x-target="locations-table locations-statistics locations-data"
        x-on:ajax:after="refresh(); close()"
      >
        <x-button.secondary>{{ __('Reopen this location') }}</x-button.secondary>
      </x-form>
    </template>

    <template x-if="location && ! location.isArchived">
      <x-button.secondary type="button" x-on:click="archiving = true">
        {{ __('Archive this location') }}
      </x-button.secondary>
    </template>
  </div>
</x-side-modal>

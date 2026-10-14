{{-- The dialog that adds a location. --}}
{{-- @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel --}}
<div
  x-cloak
  x-show="creating"
  x-transition.opacity.duration.110ms
  x-on:keydown.escape="creating = false"
  x-on:click.self="creating = false"
  :data-escape-guard="creating ? '' : null"
  class="fixed inset-0 z-70 flex justify-center overflow-y-auto bg-black/35 px-4 py-14"
>
  <div
    x-trap.inert="creating"
    role="dialog"
    aria-modal="true"
    aria-labelledby="create-location-title"
    class="h-fit w-full max-w-115 rounded-[20px] bg-canvas px-6 pt-6 pb-7 shadow-2xl ring-[1.5px] ring-hairline sm:px-7"
  >
    <h2 id="create-location-title" class="text-2xl font-bold tracking-tight text-ink">{{ __('Add a location') }}</h2>

    <p class="mt-1.5 text-[15px] leading-relaxed text-pretty text-body">
      {{ __('A name, a city and a country are enough. The address and the time zone can wait.') }}
    </p>

    <x-form method="post" :action="$viewModel->createUrl()" class="mt-5.5 space-y-4.5">
      <x-input
        id="name"
        :label="__('Name')"
        :value="old('name')"
        :placeholder="__('Berlin location')"
        :error="$errors->createLocation->get('name')"
        maxlength="255"
        required
      />

      <div class="grid gap-4.5 sm:grid-cols-2">
        <x-input
          id="city"
          :label="__('City')"
          :value="old('city')"
          :placeholder="__('Berlin')"
          :error="$errors->createLocation->get('city')"
          maxlength="255"
        />

        <x-input
          id="country"
          :label="__('Country')"
          :value="old('country')"
          :placeholder="__('DE')"
          :help="__('Two letters')"
          :error="$errors->createLocation->get('country')"
          maxlength="2"
          class="uppercase"
        />
      </div>

      <div class="flex flex-wrap items-center gap-2.5 pt-1">
        <x-button>{{ __('Add the location') }}</x-button>

        <x-button.secondary type="button" x-on:click="creating = false">{{ __('Cancel') }}</x-button.secondary>
      </div>
    </x-form>
  </div>
</div>

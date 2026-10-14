{{-- The locations a company works from, as a list with a panel over it. --}}
{{-- @var \App\ViewModels\Settings\Administration\LocationsViewModel $viewModel --}}
@php
  /* The state is built here rather than written into the tag, because blade
     compiles neither `@js` nor `{!! !!}` inside the attribute of a component,
     and the locations have to reach the browser as json. */
  $open = json_encode($viewModel->openLocationId(), JSON_THROW_ON_ERROR);
  $form = json_encode($viewModel->openLocationForm(), JSON_THROW_ON_ERROR);
  $creating = $errors->createLocation->any() ? 'true' : 'false';

  $screen = <<<JS
    {
      locations: {},
      open: {$open},
      form: {$form},
      creating: {$creating},
      archiving: false,
      unassigning: null,
      refresh() {
        this.locations = JSON.parse(document.getElementById('locations-data').textContent)
      },
      get location() {
        return this.open === null ? null : (this.locations[this.open] ?? null)
      },
      edit(id) {
        const location = this.locations[id]

        this.open = id
        this.archiving = false
        this.unassigning = null
        this.form = {
          name: location.name,
          country: location.country,
          region: location.region,
          city: location.city,
          postalCode: location.postalCode,
          addressLine1: location.addressLine1,
          addressLine2: location.addressLine2,
          phone: location.phone,
          email: location.email,
          timezone: location.timezone,
          isHeadquarters: location.isHeadquarters,
        }
      },
      close() {
        this.open = null
        this.archiving = false
        this.unassigning = null
      },
    }
    JS;
@endphp

<x-top-bar-layout :title="__('Locations')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Locations') => null,
    ]"
  />

  <div x-data="{{ $screen }}" x-init="refresh()">
    <!-- page title and the add button -->
    <div class="mb-9 grid gap-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
      <div>
        <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Locations') }}</h1>
      </div>

      @if ($viewModel->canAddLocations())
        <x-button type="button" x-on:click="creating = true" class="max-sm:w-full">{{ __('Add a location') }}</x-button>
      @endif
    </div>

    @include('app.settings.administration.locations._statistics', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._filters', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._table', ['viewModel' => $viewModel])

    <script type="application/json" id="locations-data">@json($viewModel->drawer())</script>

    <!-- note about archiving -->
    <p class="mt-4 text-sm leading-relaxed text-pretty text-muted">
      {{ __('Archiving a location keeps everything written about it. It leaves the list, so nobody can be sent to a desk that is no longer rented, and comes back whole if the company opens it again.') }}
    </p>

    @include('app.settings.administration.locations._edit-location', ['viewModel' => $viewModel])

    @include('app.settings.administration.locations._archive-location')

    @if ($viewModel->canAssignPeople())
      @include('app.settings.administration.locations._unassign-person')
    @endif

    @if ($viewModel->canAddLocations())
      @include('app.settings.administration.locations._create-location', ['viewModel' => $viewModel])
    @endif
  </div>
</x-top-bar-layout>

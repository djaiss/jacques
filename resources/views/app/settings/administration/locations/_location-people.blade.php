{{-- Who works at the location open in the panel, and the form that assigns somebody else to it. --}}
<section aria-labelledby="location-people-title" class="mt-8 border-t border-hairline-soft pt-6">
  <h3 id="location-people-title" class="text-lg font-bold tracking-tight text-ink">{{ __('People who work here') }}</h3>

  <p class="mt-1 text-sm leading-relaxed text-pretty text-muted">
    {{ __('A permission granted on their locations reaches this one for everybody listed here.') }}
  </p>

  <!-- list of people -->
  <ul class="mt-3 space-y-1" x-show="location?.people.length > 0">
    <template x-for="person in location?.people ?? []" :key="person.id">
      <li class="flex items-center gap-3 rounded-xl px-3 py-2 ring-[1.5px] ring-hairline">
        <span class="min-w-0 flex-1 text-base font-semibold wrap-anywhere text-ink" x-text="person.name"></span>

        <button
          type="button"
          x-on:click="unassigning = person"
          aria-haspopup="dialog"
          class="cursor-pointer rounded-md px-1.5 py-1 text-[15px] font-semibold text-muted transition-colors hover:text-error focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
        >{{ __('Remove') }}<span class="sr-only" x-text="' ' + person.name"></span></button>
      </li>
    </template>
  </ul>

  <p class="mt-3 text-[15px] text-muted" x-show="location?.people.length === 0">{{ __('Nobody is assigned to this location yet.') }}</p>

  <!-- assign somebody -->
  <x-form
    method="post"
    x-bind:action="location?.assignUrl"
    x-target="locations-table locations-statistics locations-data"
    x-on:ajax:after="refresh()"
    x-show="location?.assignable.length > 0"
    class="mt-4 flex flex-wrap items-end gap-2.5"
  >
    <div class="min-w-48 flex-1">
      <label for="location-assignee" class="mb-1.5 block text-sm font-semibold text-ink">{{ __('Assign somebody') }}</label>

      <select
        id="location-assignee"
        name="staff"
        required
        class="block w-full rounded-xl border-[1.5px] border-control bg-input px-3.5 py-2.5 text-base text-ink transition-colors hover:border-ink focus:border-focus-ring focus:ring-2 focus:ring-focus-ring focus:outline-none"
      >
        <template x-for="person in location?.assignable ?? []" :key="person.id">
          <option x-bind:value="person.id" x-text="person.name"></option>
        </template>
      </select>
    </div>

    <x-button.secondary>{{ __('Assign') }}</x-button.secondary>
  </x-form>
</section>

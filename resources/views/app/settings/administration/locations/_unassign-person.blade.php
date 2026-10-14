{{-- The dialog that asks before somebody is taken off a location. --}}
<x-confirm-dialog
  show="unassigning !== null"
  close="unassigning = null"
  labelledby="unassign-person-title"
  :title="__('Take this person off the location?')"
  :cancel="__('Keep them')"
>
  {{ __('What their roles grant on their locations stops reaching this one. Their other locations and their roles stay as they are.') }}

  <x-slot:actions>
    <x-form
      method="delete"
      x-bind:action="unassigning?.removeUrl"
      x-target="locations-table locations-statistics locations-data"
      x-on:ajax:after="refresh(); unassigning = null"
    >
      <x-button.danger>{{ __('Take them off') }}</x-button.danger>
    </x-form>
  </x-slot:actions>
</x-confirm-dialog>

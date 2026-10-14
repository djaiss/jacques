{{-- The dialog that asks before a location is archived. --}}
<x-confirm-dialog
  show="archiving"
  close="archiving = false"
  labelledby="archive-location-title"
  :title="__('Archive this location?')"
  :cancel="__('Keep it open')"
>
  {{ __('The location leaves the list and nobody can be sent to it. Everything written about it is kept, and reopening it brings all of it back.') }}

  <x-slot:actions>
    <x-form
      method="post"
      x-bind:action="location?.archiveUrl"
      x-target="locations-table locations-statistics locations-data"
      x-on:ajax:after="refresh(); close()"
    >
      <x-button.danger>{{ __('Archive it') }}</x-button.danger>
    </x-form>
  </x-slot:actions>
</x-confirm-dialog>

{{-- The side panel of the dashboard: one appointment and its cancellation, or a new booking. --}}
{{-- @var list<array{name: string, duration: int}> $services --}}
@php
  $selectClasses = 'block w-full cursor-pointer appearance-none rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink transition-colors duration-150 hover:border-ink hover:bg-hover focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none';
@endphp

<x-side-modal show="panel !== null" close="closePanel()" labelledby="appointment-panel-title">
  <x-slot:header>
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase" x-text="panel === 'book' ? @js(__('New appointment')) : @js(__('Appointment'))"></p>
    <h2 id="appointment-panel-title" class="mt-1 text-[26px] font-bold tracking-[-0.025em] text-ink" x-text="panel === 'book' ? draftTitle : selected?.client"></h2>
    <p x-show="panel === 'book'" class="mt-1 text-sm text-body" x-text="sentences.draftHelp"></p>
  </x-slot>

  <!-- appointment details -->
  <template x-if="panel === 'view' && selected !== null">
    <div class="space-y-6">
      <dl class="overflow-hidden rounded-xl border border-hairline text-sm">
        <div class="flex justify-between gap-3 border-b border-hairline-soft px-3.5 py-3">
          <dt class="text-muted">{{ __('Service') }}</dt>
          <dd class="text-right font-bold text-ink" x-text="selected.serviceDetail"></dd>
        </div>
        <div class="flex justify-between gap-3 border-b border-hairline-soft px-3.5 py-3">
          <dt class="text-muted">{{ __('Hairdresser') }}</dt>
          <dd class="text-right font-bold text-ink" x-text="selected.hairdresserName"></dd>
        </div>
        <div class="flex justify-between gap-3 px-3.5 py-3">
          <dt class="text-muted">{{ __('Time') }}</dt>
          <dd class="text-right font-bold text-ink first-letter:uppercase" x-text="selected.when"></dd>
        </div>
      </dl>

      <div x-show="! confirming">
        <x-button.secondary type="button" x-on:click="confirming = true">{{ __('Cancel this appointment') }}</x-button.secondary>
      </div>

      <!-- cancellation warning -->
      <div x-show="confirming" role="alert" class="space-y-3">
        <p class="text-sm text-body">{{ __('The appointment leaves the agenda and the slot becomes free again. Are you sure?') }}</p>

        <div class="flex gap-2">
          <x-button.danger type="button" x-on:click="cancel()">{{ __('Cancel it') }}</x-button.danger>
          <x-button.secondary type="button" x-on:click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
        </div>
      </div>
    </div>
  </template>

  <!-- new appointment -->
  <template x-if="panel === 'book' && draft !== null">
    <form x-on:submit.prevent="add()" class="space-y-5">
      <x-input id="appointment-client" :label="__('Client name')" :placeholder="__('For example, Julie Bernard')" x-model="draft.client" autocomplete="off" />

      <div class="space-y-1.5">
        <x-label for="appointment-service" :value="__('Service')" />
        <select id="appointment-service" x-model.number="draft.service" class="{{ $selectClasses }}">
          @foreach ($services as $index => $service)
            <option value="{{ $index }}">{{ __(':service (:minutes min)', ['service' => $service['name'], 'minutes' => $service['duration']]) }}</option>
          @endforeach
        </select>
      </div>

      <div class="flex gap-2">
        <x-button x-bind:disabled="! canAdd()">{{ __('Add the appointment') }}</x-button>
        <x-button.secondary type="button" x-on:click="closePanel()">{{ __('Close') }}</x-button.secondary>
      </div>
    </form>
  </template>
</x-side-modal>

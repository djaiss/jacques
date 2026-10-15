{{-- The panel that adds or edits one team member: who they are, their account and their services. --}}
{{-- @var \App\ViewModels\Onboarding\StaffPanel $panel --}}
@php
  $close = "\$ajax('".route('onboarding.staff.index')."', { target: 'staff-panel' })";
  $values = $panel->values();
  $box = 'mt-px flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-control bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring peer-disabled:opacity-50';
  $tick = '<svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path></svg>';
  $textLink = 'cursor-pointer px-0 py-1 text-sm text-body underline underline-offset-3 hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none';
  $preview = $panel->preview();
  $everyId = collect($panel->categories())->flatMap(fn (array $category): array => array_column($category['services'], 'id'))->all();
  $state = '{
    first: '.Js::from($values['first_name']).',
    last: '.Js::from($values['last_name']).',
    display: '.Js::from($values['display_name']).',
    job: '.Js::from($values['job_title']).',
    email: '.Js::from($values['email']).',
    bookable: '.Js::from($values['is_bookable']).',
    invite: '.Js::from($values['invite']).',
    selected: '.Js::from(array_map(strval(...), $values['services'])).',
    everyId: '.Js::from(array_map(strval(...), $everyId)).',
    confirming: false,
    previewing: false,
    title() {
      return this.display.trim() || [this.first.trim(), this.last.trim()].filter(Boolean).join(" ") || '.Js::from($panel->newMemberTitle()).'
    },
    hasEmail() {
      return this.email.trim() !== ""
    },
    countIn(ids) {
      return ids.filter((id) => this.selected.includes(id)).length
    },
    setCategory(ids, on) {
      const others = this.selected.filter((id) => ! ids.includes(id))
      this.selected = on ? [...others, ...ids] : others
    },
    fill(text) {
      return text
        .replaceAll(":first_name", this.first.trim())
        .replaceAll(":job_title", this.job.trim())
        .replaceAll(":email", this.email.trim())
    },
  }';
@endphp

<x-side-modal show="true" :close="$close" labelledby="staff-panel-title" :x-data="$state">
  <x-slot:header>
    <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ $panel->eyebrow() }}</p>
    <h2 id="staff-panel-title" class="mt-1.5 text-[26px] font-bold tracking-[-0.025em] wrap-anywhere text-ink" x-text="title()"></h2>
  </x-slot>

  <div class="space-y-5">
    <x-form
      method="{{ $panel->isNew() ? 'post' : 'put' }}"
      :action="$panel->formAction()"
      id="staff-panel-form"
      x-target="team-list team-footer staff-panel"
      class="space-y-5"
    >
      <!-- identity -->
      <div class="grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] gap-4">
        <div class="space-y-1.5">
          <x-label for="staff-first-name">{{ __('First name') }} <span class="text-error" aria-hidden="true">*</span></x-label>
          <x-input id="staff-first-name" name="first_name" :value="$values['first_name']" x-model="first" placeholder="Pam" autocomplete="off" required :error="$errors->get('first_name')" />
        </div>
        <x-input id="staff-last-name" name="last_name" :label="__('Last name')" :value="$values['last_name']" x-model="last" placeholder="Beesly" autocomplete="off" :error="$errors->get('last_name')" />
      </div>

      <x-input id="staff-display-name" name="display_name" :label="__('Name shown to clients')" :value="$values['display_name']" x-model="display" x-bind:placeholder="[first.trim(), last.trim()].filter(Boolean).join(' ')" :help="__('Leave it empty to use the full name.')" autocomplete="off" :error="$errors->get('display_name')" />

      <x-input id="staff-job-title" name="job_title" :label="__('Job title')" :value="$values['job_title']" x-model="job" :placeholder="__('Hairstylist')" autocomplete="off" :error="$errors->get('job_title')" />

      <div class="grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] gap-4">
        <x-input id="staff-email" type="email" name="email" :label="__('Email')" :value="$values['email']" x-model="email" placeholder="pam@example.com" autocomplete="off" :disabled="$panel->isOwnRecord()" :error="$errors->get('email')" />
        <x-input id="staff-phone" type="tel" name="phone" :label="__('Phone')" :value="$values['phone']" placeholder="+1 570 555 0100" autocomplete="off" :error="$errors->get('phone')" />
      </div>

      <div class="space-y-1.5">
        <x-label for="staff-bio">{{ __('Bio') }}</x-label>
        <textarea
          id="staff-bio"
          name="bio"
          rows="3"
          maxlength="1000"
          placeholder="{{ __('A few words clients see when they choose who to book with. Optional.') }}"
          class="block w-full resize-y rounded-[10px] border-[1.5px] border-control bg-input px-3 py-2.5 text-base leading-snug text-ink placeholder:text-placeholder hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none"
        >{{ $values['bio'] }}</textarea>
      </div>

      <!-- account -->
      <div class="space-y-3 border-t border-hairline-soft pt-5">
        <h3 class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Account') }}</h3>

        @if ($panel->isOwnRecord())
          <p class="text-sm leading-normal text-pretty text-body">{{ __('This is your own account. Your email address is the one you sign in with, and you change it from your profile.') }}</p>
        @elseif (! $panel->canBeInvited())
          <p class="text-sm leading-normal text-pretty text-body">{{ __('This person already has an account.') }}</p>
        @else
          <div class="space-y-3">
            <input type="hidden" name="invite" value="0" />
            <label for="staff-invite" class="relative flex cursor-pointer items-start gap-2.5">
              <input type="checkbox" id="staff-invite" name="invite" value="1" x-model="invite" x-bind:disabled="! hasEmail()" aria-describedby="staff-invite-help" class="peer sr-only" />
              <span aria-hidden="true" class="{{ $box }}">{!! $tick !!}</span>
              <span class="text-[15px] leading-normal text-body">{{ __('Send an invitation to create an account') }}</span>
            </label>

            <div class="space-y-3 pl-6.5">
              <p id="staff-invite-help" class="text-sm leading-normal text-pretty text-muted">
                <span x-show="! hasEmail()">{{ __('Add an email address to invite this person. Without an account, they can still be booked, and you manage their schedule for them.') }}</span>
                <span x-show="hasEmail() && invite" x-cloak x-text="fill(@js(__('The invitation is kept ready for :email. With an account, they see their appointments and manage their own availability.')))"></span>
                <span x-show="hasEmail() && ! invite" x-cloak>{{ __('No invitation is prepared. You can invite them later from Settings.') }}</span>
              </p>

              <div x-show="hasEmail() && invite" x-cloak class="space-y-1.5">
                <x-label for="staff-role">{{ __('Role of the account') }}</x-label>
                <select id="staff-role" name="role" x-bind:disabled="! (hasEmail() && invite)" class="block w-full cursor-pointer rounded-[10px] border-[1.5px] border-control bg-input px-3.25 py-2.75 text-base text-ink hover:border-ink focus:border-focus-ring focus:bg-canvas focus:ring-2 focus:ring-focus-ring focus:outline-none">
                  @foreach ($panel->roleOptions() as $role)
                    <option value="{{ $role['id'] }}" @selected($role['id'] === $values['role'])>{{ $role['name'] }}</option>
                  @endforeach
                </select>
              </div>

              <div x-show="hasEmail()" x-cloak>
                <x-button.secondary type="button" x-on:click="previewing = true" class="py-2" id="staff-preview-button">
                  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3.5" width="12" height="9" rx="1.5"></rect><path d="M2.5 4.5L8 9l5.5-4.5"></path></svg>
                  {{ __('Preview the email') }}
                </x-button.secondary>
              </div>
            </div>
          </div>
        @endif
      </div>

      <!-- services -->
      <div class="space-y-3.5 border-t border-hairline-soft pt-5">
        <h3 class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Services') }}</h3>

        <input type="hidden" name="is_bookable" value="0" />
        <label for="staff-bookable" class="relative flex cursor-pointer items-start gap-2.5">
          <input type="checkbox" id="staff-bookable" name="is_bookable" value="1" x-model="bookable" class="peer sr-only" />
          <span aria-hidden="true" class="{{ $box }}">{!! $tick !!}</span>
          <span class="text-[15px] leading-normal text-body">{{ __('Clients can book appointments with this person') }}</span>
        </label>

        <p x-show="! bookable" x-cloak class="pl-6.5 text-sm leading-normal text-pretty text-muted">{{ __('This person stays on the team but does not appear when clients book. Their services are kept for when you change your mind.') }}</p>

        @if ($panel->serviceTotal() === 0)
          <p x-show="bookable" class="text-sm leading-normal text-pretty text-muted">{{ __('Your catalog has no service yet. Add services at the previous step, then choose who provides them.') }}</p>
        @else
          <div x-show="bookable" class="space-y-2.5">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <span class="text-sm font-semibold tabular-nums" x-bind:class="selected.length === 0 ? 'text-warning' : 'text-ink'" aria-live="polite">
                <span x-show="selected.length !== everyId.length" x-text="@js($panel->selectionSentence()).replace(':selected', selected.length)"></span>
                <span x-show="selected.length === everyId.length" x-cloak>{{ $panel->fullSelectionSentence() }}</span>
              </span>
              <span class="flex gap-3.5">
                <button type="button" x-on:click="selected = [...everyId]" class="{{ $textLink }}">{{ __('Select all') }}</button>
                <button type="button" x-on:click="selected = []" class="{{ $textLink }}">{{ __('Clear') }}</button>
              </span>
            </div>

            <div class="overflow-hidden rounded-xl border border-hairline">
              @foreach ($panel->categories() as $category)
                @php
                  $ids = array_column($category['services'], 'id');
                @endphp
                <fieldset class="space-y-2.5 border-b border-hairline-soft px-3.5 py-3 last:border-b-0" x-data="{ ids: @js(array_map(strval(...), $ids)) }">
                  <legend class="sr-only">{{ $category['name'] }}</legend>
                  <div class="flex items-center justify-between gap-2.5">
                    <label for="staff-category-{{ $loop->index }}" class="relative flex cursor-pointer items-start gap-2.5">
                      <input
                        type="checkbox"
                        id="staff-category-{{ $loop->index }}"
                        x-bind:checked="countIn(ids) === ids.length"
                        x-effect="$el.indeterminate = countIn(ids) > 0 && countIn(ids) < ids.length"
                        x-on:change="setCategory(ids, $event.target.checked)"
                        class="peer sr-only"
                      />
                      <span aria-hidden="true" class="{{ $box }}">{!! $tick !!}</span>
                      <span class="text-[15px] leading-normal font-bold text-ink">{{ $category['name'] }}</span>
                    </label>
                    <span class="text-xs text-muted tabular-nums" x-text="@js(__(':selected of :total')).replace(':selected', countIn(ids)).replace(':total', ids.length)"></span>
                  </div>

                  <div class="grid grid-cols-[repeat(auto-fill,minmax(170px,1fr))] gap-x-3.5 gap-y-2 pl-6.5">
                    @foreach ($category['services'] as $service)
                      <label for="staff-service-{{ $service['id'] }}" class="relative flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" id="staff-service-{{ $service['id'] }}" name="services[]" value="{{ $service['id'] }}" x-model="selected" class="peer sr-only" />
                        <span aria-hidden="true" class="{{ $box }}">{!! $tick !!}</span>
                        <span class="text-sm leading-normal text-body">{{ $service['name'] }}</span>
                      </label>
                    @endforeach
                  </div>
                </fieldset>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </x-form>

    <!-- panel actions -->
    <div class="flex flex-wrap items-center justify-between gap-2.5 border-t border-hairline-soft pt-5">
      <div class="flex flex-wrap items-center gap-2.5">
        @if ($panel->destroyUrl() !== null)
          <button type="button" x-show="! confirming" x-on:click="confirming = true; $nextTick(() => $refs.keep.focus())" class="cursor-pointer py-2 text-sm text-error hover:underline">{{ __('Remove from the team') }}</button>
          <div x-show="confirming" x-cloak role="alert" class="flex flex-wrap items-center gap-2.5">
            <span class="text-sm text-body">{{ $panel->removalQuestion() }}</span>
            <x-form method="delete" :action="$panel->destroyUrl()" x-target="team-list team-footer staff-panel">
              <button type="submit" class="cursor-pointer py-2 text-sm font-semibold text-error hover:underline">{{ __('Remove them') }}</button>
            </x-form>
            <button type="button" x-ref="keep" x-on:click="confirming = false" class="cursor-pointer py-2 text-sm text-body hover:text-ink">{{ __('Keep them') }}</button>
          </div>
        @endif
      </div>

      <div class="ml-auto flex gap-2.5">
        <x-button.secondary type="button" x-on:click="{{ $close }}">{{ __('Cancel') }}</x-button.secondary>
        <x-button type="submit" form="staff-panel-form">{{ $panel->isNew() ? __('Add to the team') : __('Save changes') }}</x-button>
      </div>
    </div>

    @if ($panel->canBeInvited())
      <!-- invitation preview -->
      <div
        x-show="previewing"
        x-cloak
        x-on:keydown.escape.stop="previewing = false"
        x-on:click.self="previewing = false"
        class="absolute inset-0 z-10 flex items-start justify-center overflow-y-auto bg-black/35 p-3"
      >
        <div x-trap="previewing" role="dialog" aria-modal="true" aria-labelledby="staff-preview-title" class="w-full rounded-[20px] bg-canvas shadow-2xl ring-1 ring-hairline">
          <div class="flex items-start justify-between gap-4 px-5 pt-5 pb-4">
            <div class="space-y-1.5">
              <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Email preview') }}</p>
              <h3 id="staff-preview-title" class="text-2xl font-bold tracking-[-0.025em] text-ink" x-text="first.trim() !== '' ? fill(@js($preview['title'])) : @js($preview['titleWithoutName'])"></h3>
            </div>
            <button type="button" x-on:click="previewing = false" aria-label="{{ __('Close the preview') }}" class="flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none">
              <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line><line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line></svg>
            </button>
          </div>

          <dl class="grid grid-cols-[64px_minmax(0,1fr)] gap-x-3 gap-y-2 border-b border-hairline-soft px-5 pb-4 text-sm">
            <dt class="text-muted">{{ __('From') }}</dt>
            <dd class="wrap-anywhere text-ink">{{ $preview['from'] }}</dd>
            <dt class="text-muted">{{ __('To') }}</dt>
            <dd class="wrap-anywhere text-ink" x-text="`${[first.trim(), last.trim()].filter(Boolean).join(' ')} <${email.trim()}>`.trim()"></dd>
            <dt class="text-muted">{{ __('Subject') }}</dt>
            <dd class="font-semibold text-ink">{{ $preview['subject'] }}</dd>
          </dl>

          <div class="bg-page px-4 py-6">
            <div class="mx-auto flex max-w-120 flex-col gap-4.5 rounded-xl bg-white px-6 pt-7 pb-6 text-[#17161a] ring-1 ring-hairline">
              <p class="text-[15px] font-bold tracking-[-0.01em]">{{ $panel->salonName() }}</p>
              <p class="text-[22px] font-bold tracking-[-0.025em]" x-text="first.trim() !== '' ? fill(@js($preview['greeting'])) : @js($preview['greetingWithoutName'])"></p>
              <p class="text-[15px] leading-relaxed text-pretty text-[#5f5b54]" x-text="job.trim() !== '' ? fill(@js($preview['body'])) : @js($preview['bodyWithoutJob'])"></p>
              <p class="text-[15px] leading-relaxed text-pretty text-[#5f5b54]">{{ $preview['purpose'] }}</p>
              <span class="self-start rounded-[10px] bg-[#17161a] px-5 py-3 text-[15px] font-semibold text-white">{{ $preview['button'] }}</span>
              <p class="border-t border-[#e9e5dc] pt-4 text-xs leading-normal text-pretty text-[#6e6a62]">{{ $preview['footer'] }}</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
            <span class="text-sm text-muted">{{ $preview['when'] }}</span>
            <x-button type="button" x-on:click="previewing = false">{{ __('Close the preview') }}</x-button>
          </div>
        </div>
      </div>
    @endif
  </div>
</x-side-modal>

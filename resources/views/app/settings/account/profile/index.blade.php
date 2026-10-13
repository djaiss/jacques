{{-- Where somebody edits their own employee record. --}}
{{-- @var \App\ViewModels\Settings\Account\Profile\ProfileViewModel $viewModel --}}
<x-top-bar-layout :title="__('Profile')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Profile') => null,
    ]"
  />

  <!-- page title -->
  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Profile') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('Manage your personal details and how colleagues see you.') }}</p>
  </div>

  <div class="space-y-10">
    <!-- avatar -->
    <x-section :title="__('Avatar')" icon="profile" :hue="30">
      <x-slot:help>
        <x-help :title="__('Avatar')">
          {{ __('The avatar is stored on our own servers, never on somebody else\'s, and is only ever served to people allowed to see you. Removing it puts your initials back everywhere.') }}
        </x-help>
      </x-slot:help>

      <div class="grid items-center gap-7 md:grid-cols-[minmax(0,1fr)_auto]">
        <div class="space-y-2 text-[15px] leading-relaxed text-body">
          <p>{{ __('Your avatar appears next to your name across :app.', ['app' => config('app.name')]) }}</p>
          <p>{{ __('Use a square image for the best result. JPEG, PNG and WebP up to 5 MB.') }}</p>
          <p>{{ __('Without an avatar, we show your initials instead.') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-5">
          <x-avatar id="profile-avatar" :employee="$viewModel->employee()" :name="$viewModel->name()" :size="78" />

          <div
            class="space-y-3 max-sm:w-full"
            x-data="{ tooBig: false, tooBigMessage: @js(__('The image must be under 5 MB.')), removingAvatar: false }"
          >
            <x-form
              method="post"
              :action="route('settings.avatar.update')"
              :upload="true"
              id="avatar-form"
              class="space-y-3"
              x-on:submit="if (tooBig) $event.preventDefault()"
            >
              <x-file-input
                id="avatar"
                accept="image/jpeg,image/png,image/webp"
                :error="$errors->get('avatar')"
                required
                x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
              />

              <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-sm text-error"></p>

              <x-button>{{ __('Upload') }}</x-button>
            </x-form>

            @if ($viewModel->hasAvatar())
              <button
                type="button"
                x-on:click="removingAvatar = true"
                class="cursor-pointer text-sm text-muted hover:text-ink"
              >{{ __('Remove the avatar') }}</button>

              <x-confirm-dialog
                show="removingAvatar"
                close="removingAvatar = false"
                labelledby="remove-avatar-title"
                :title="__('Remove your avatar?')"
                :cancel="__('Keep it')"
              >
                {{ __('Your initials go back everywhere your avatar is shown. Uploading another one puts an avatar back.') }}

                <x-slot:actions>
                  <x-form method="delete" :action="route('settings.avatar.destroy')" id="avatar-delete-form">
                    <x-button.danger>{{ __('Remove it') }}</x-button.danger>
                  </x-form>
                </x-slot:actions>
              </x-confirm-dialog>
            @endif
          </div>
        </div>
      </div>
    </x-section>

    <!-- name and email -->
    <x-section :title="__('Details')" icon="details" :hue="250">
      <x-slot:help>
        <x-help :title="__('Details')">
          {{ __('Your first and last name are how you are listed everywhere. A display name replaces them on screen without changing the record itself, which is what you want if you go by something other than your legal name.') }}
        </x-help>
      </x-slot:help>

      <div class="grid gap-10 md:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <div class="space-y-2.5 text-[15px] leading-relaxed text-body">
          <p>{{ __('These details are shown on your profile. Everyone in your company can see them.') }}</p>
        </div>

        <x-form
          method="put"
          :action="route('settings.profile.update')"
          id="details-form"
          x-target="details-form profile-avatar top-bar-identity"
          class="space-y-5 transition-opacity [&[aria-busy]]:opacity-60"
        >
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-input
              id="first_name"
              :label="__('First name')"
              :value="$viewModel->details()['first_name']"
              :error="$errors->get('first_name')"
              required
            />

            <x-input
              id="last_name"
              :label="__('Last name')"
              :value="$viewModel->details()['last_name']"
              :error="$errors->get('last_name')"
              required
            />
          </div>

          <x-input
            id="display_name"
            :label="__('Display name')"
            :value="$viewModel->details()['display_name']"
            :placeholder="__('Shown instead of your real name')"
            :help="__('Optional.')"
            :error="$errors->get('display_name')"
          />

          <x-input
            type="email"
            id="work_email"
            :label="__('Work email')"
            :value="$viewModel->details()['work_email']"
            :error="$errors->get('work_email')"
          />

          <div class="flex items-center">
            <x-button class="ml-auto">{{ __('Save') }}</x-button>
          </div>
        </x-form>
      </div>
    </x-section>
  </div>
</x-top-bar-layout>

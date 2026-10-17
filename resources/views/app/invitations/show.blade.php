{{-- Where a staff member lands from their invitation email, to join the salon that invited them. --}}
{{-- @var \App\ViewModels\Invitation\InvitationViewModel $viewModel --}}
<x-guest-layout :title="$viewModel->title()">
  <main class="flex min-h-screen min-w-0 flex-col px-4 pt-10 pb-8 sm:px-15 sm:pt-14">
    <div class="mx-auto w-full max-w-md space-y-6">
      <!-- logo -->
      <div class="flex items-center gap-3">
        <x-logo />

        <span class="text-lg font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>

        <x-theme-toggle class="ml-auto" />
      </div>

      <!-- invitation -->
      <x-box>
        <div class="space-y-5">
          <div class="space-y-2">
            <h1 class="text-2xl font-bold tracking-[-0.025em] text-balance text-ink">{{ $viewModel->title() }}</h1>
            <p class="text-[15px] leading-relaxed text-pretty text-body">{{ $viewModel->message() }}</p>
          </div>

          @if ($viewModel->isError())
            @if ($viewModel->variant() === \App\ViewModels\Invitation\InvitationViewModel::ACCEPTED)
              <x-button :href="$viewModel->signInUrl()" class="w-full whitespace-normal">{{ __('Sign in') }}</x-button>
            @endif
          @else
            <dl class="grid grid-cols-1 gap-y-3 rounded-[10px] bg-sunken px-4 py-3.5 text-sm">
              <div class="space-y-0.5">
                <dt class="text-muted">{{ __('Your name') }}</dt>
                <dd class="font-semibold wrap-anywhere text-ink">{{ $viewModel->staffName() }}</dd>
              </div>
              <div class="space-y-0.5">
                <dt class="text-muted">{{ __('Email address') }}</dt>
                <dd class="font-semibold wrap-anywhere text-ink">{{ $viewModel->email() }}</dd>
              </div>
            </dl>

            @switch($viewModel->variant())
              @case(\App\ViewModels\Invitation\InvitationViewModel::CREATE_ACCOUNT)
                <!-- new account form -->
                <x-form method="post" :action="$viewModel->acceptUrl()" class="space-y-4">
                  <input type="text" name="email" value="{{ $viewModel->email() }}" autocomplete="username" class="hidden" aria-hidden="true" tabindex="-1" readonly />

                  <x-input
                    type="password"
                    id="password"
                    :label="__('Create a password')"
                    :help="__('Minimum 8 characters.')"
                    autocomplete="new-password"
                    passwordrules="minlength: 8"
                    :error="$errors->get('password')"
                    allowPasswordManager
                    required
                    autofocus
                  />

                  <x-button class="w-full">{{ __('Create account & join team') }}</x-button>
                </x-form>

                <p class="text-center text-sm text-body">
                  {{ __('Already have a Jacques account?') }}
                  <x-link :href="$viewModel->signInUrl()">{{ __('Sign in instead.') }}</x-link>
                </p>
                @break

              @case(\App\ViewModels\Invitation\InvitationViewModel::SIGN_IN)
                <!-- existing account -->
                <p class="text-sm leading-normal text-pretty text-body">{{ __('This address already has an account. Sign in to accept the invitation.') }}</p>
                <x-button :href="$viewModel->signInUrl()" class="w-full whitespace-normal">{{ __('Sign in to join :salon', ['salon' => $viewModel->salonName()]) }}</x-button>
                @break

              @case(\App\ViewModels\Invitation\InvitationViewModel::CONFIRM)
                <!-- confirmation -->
                <x-form method="post" :action="$viewModel->acceptUrl()">
                  <x-button class="w-full">{{ __('Join :salon', ['salon' => $viewModel->salonName()]) }}</x-button>
                </x-form>
                @break

              @case(\App\ViewModels\Invitation\InvitationViewModel::SWITCH_ACCOUNT)
                <!-- account switch -->
                <x-form method="post" :action="$viewModel->signOutUrl()">
                  <x-button class="w-full">{{ __('Sign out and continue') }}</x-button>
                </x-form>
                @break
            @endswitch
          @endif
        </div>
      </x-box>
    </div>

    <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
      &copy; {{ config('app.name') }} {{ now()->format('Y') }}
    </p>
  </main>
</x-guest-layout>

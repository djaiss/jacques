{{-- Where somebody who already has an account signs back in. --}}
{{-- @var \App\ViewModels\Auth\SignInViewModel $viewModel --}}
<x-guest-layout :title="__('Welcome back')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col px-6 pt-10 pb-8 sm:px-15 sm:pt-14">
      <div class="mx-auto w-full max-w-md space-y-6">

        <!-- logo -->
        <div class="flex items-center gap-3">
          <x-logo :size="30" />

          <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Welcome back') }}</h1>

          <x-theme-toggle class="ml-auto" />
        </div>

        <x-status :message="session('status')" />

        <!-- login form -->
        <x-box>
          <x-form method="post" :action="route('auth.signIn.create')" class="space-y-4">
            <x-input
              type="email"
              id="email"
              :label="__('Email address')"
              :value="old('email')"
              placeholder="john@doe.com"
              autocomplete="username"
              :error="$errors->get('email')"
              allowPasswordManager
              required
              autofocus
            />

            <x-input
              type="password"
              id="password"
              :label="__('Password')"
              autocomplete="current-password"
              :error="$errors->get('password')"
              allowPasswordManager
              required
            />

            <x-checkbox id="remember" class="w-fit" :checked="(bool) old('remember')">
              {{ __('Remember me for :count days', ['count' => config('jacques.remember_duration_days')]) }}
            </x-checkbox>

            <x-turnstile data-size="flexible" />

            <div class="flex items-center gap-3.5">
              <x-link turbo :href="route('auth.password.new')" class="text-sm text-body">
                {{ __('Forgot your password?') }}
              </x-link>

              <x-button class="ml-auto">{{ __('Continue') }}</x-button>
            </div>
          </x-form>
        </x-box>

        <!-- local sign in -->
        @if($viewModel->localSignInEmail())
          <x-box padding="p-4" class="rounded-lg text-center text-sm text-body">
            <x-form method="post" :action="route('auth.localSignIn.create')">
              <button
                type="submit"
                class="inline cursor-pointer font-semibold text-ink underline underline-offset-2 decoration-hairline-strong transition-colors duration-150 hover:text-brand hover:decoration-brand"
              >
                Sign in as {{ $viewModel->localSignInEmail() }}
              </button>
            </x-form>
          </x-box>
        @endif

        <!-- link to magic link -->
        <x-box padding="p-4" class="rounded-lg text-center text-sm text-body">
          {{ __('Prefer to sign in without a password?') }}
          <x-link turbo :href="route('auth.magicLink.new')" class="font-semibold text-ink">{{ __('Send me a link instead') }}</x-link>
        </x-box>

        <!-- link to register page -->
        <x-box padding="p-4" class="rounded-lg text-center text-sm text-body">
          {{ __('New to :app?', ['app' => config('app.name')]) }}
          <x-link turbo :href="route('auth.register.new')" class="font-semibold text-ink">{{ __('Create a company') }}</x-link>
        </x-box>

        <!-- language picker -->
        <div class="flex items-center gap-3">
          <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />

          <span class="text-xs text-muted-soft">{{ __('You can change it any time.') }}</span>
        </div>
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>

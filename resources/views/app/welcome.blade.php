{{-- The letter a new company reads once, right after signing up, before anything else. --}}
{{-- @var \App\ViewModels\WelcomeViewModel $viewModel --}}
<x-guest-layout :title="__('Welcome to Jacques')">
  <main class="flex min-h-screen justify-center px-4 py-12 text-ink sm:px-8 sm:py-18">
    <div class="flex w-full max-w-155 flex-col items-center space-y-10">

      <!-- greeting -->
      <div class="flex flex-col items-center space-y-3.5 text-center">
        <x-logo class="mb-3.5" />

        <p class="text-xs font-bold tracking-[0.08em] text-muted uppercase">{{ __('Welcome to Jacques') }}</p>

        <h1 class="text-3xl leading-tight font-bold tracking-[-0.025em] text-balance sm:text-4xl">
          {{ __('Hi :name, welcome to Jacques.', ['name' => $viewModel->firstName()]) }}
        </h1>

        <p class="max-w-115 text-lg leading-relaxed text-pretty text-muted">
          {{ __('A little less time managing your salon. A little more time doing what you love.') }}
        </p>
      </div>

      <!-- letter from the creator -->
      <div class="w-full">
        <x-box padding="p-6 sm:p-9">
          <div class="space-y-4.5 text-base leading-[1.7] text-pretty text-body">
            <p>{{ __('I started Jacques with a simple idea: running a salon should be as enjoyable as the work you do every day. Appointments, clients, schedules, and all the little things that keep a business running shouldn’t get in the way of what matters most.') }}</p>
            <p>{{ __('Jacques is my attempt to make that happen. It’s open source, built in the open, and designed to make the everyday life of salon owners and their teams a little easier.') }}</p>
            <p>{{ __('I’m building Jacques on my own, one improvement at a time. There isn’t a big company behind it, just someone who cares deeply about making useful software. Your feedback, ideas, and even frustrations will help shape what Jacques becomes.') }}</p>
            <p>{{ __('For now, let’s start with your salon. Set up your first location, add your services, and invite your team whenever you’re ready. There’s no rush.') }}</p>
            <p>{{ __('Thank you for giving Jacques a chance. It genuinely means a lot.') }}</p>
          </div>

          <!-- signature -->
          <div class="mt-7 flex items-center gap-3.5 border-t border-hairline pt-6">
            <x-avatar-initials name="Regis" :size="44" aria-hidden="true" />

            <div class="space-y-0.5">
              <p class="text-[17px] font-bold tracking-[-0.01em] text-ink">Regis</p>
              <p class="text-sm text-muted">{{ __('Creator of Jacques') }}</p>
            </div>
          </div>
        </x-box>
      </div>

      <!-- start button -->
      <x-form method="put" :action="route('welcome.update')" data-turbo="true">
        <x-button>
          {{ __('Let’s get started') }}
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 8h10M9 4l4 4-4 4" />
          </svg>
        </x-button>
      </x-form>
    </div>
  </main>
</x-guest-layout>

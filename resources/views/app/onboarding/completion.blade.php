{{-- The screen that closes the onboarding, where the owner may invite the team they prepared. --}}
{{-- @var \App\ViewModels\Onboarding\CompletionViewModel $viewModel --}}
<x-top-bar-layout :title="$viewModel->title()">
  <!-- page title -->
  <div class="max-w-180 space-y-2">
    <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] text-balance text-ink">{{ $viewModel->title() }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('The location, the services, the team and their hours are set. You can change any of them later from Settings.') }}</p>
  </div>

  @if ($viewModel->hasPreparedInvitations())
    <!-- invite your team -->
    <section aria-labelledby="invite-team-title" class="mt-10 max-w-180 space-y-4 rounded-[18px] bg-canvas p-6 ring-[1.5px] ring-hairline">
      <div class="space-y-1.5">
        <h2 id="invite-team-title" class="text-[22px] font-bold tracking-tight text-ink">{{ __('Invite your team') }}</h2>
        <p class="text-[15px] leading-relaxed text-pretty text-body">{{ $viewModel->invitationSentence() }} {{ __('Each person receives an email to create their account. Nothing has been sent yet.') }}</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <x-form method="post" :action="route('onboarding.completion.invitations.create')">
          <x-button>{{ $viewModel->sendLabel() }}</x-button>
        </x-form>

        <x-button.secondary :href="route('dashboard.index')">{{ __('Not now') }}</x-button.secondary>
      </div>

      <p class="text-sm leading-normal text-pretty text-muted">{{ __('You can send them later from Settings, in Team members.') }}</p>
    </section>
  @else
    <div class="mt-8">
      <x-button :href="route('dashboard.index')">{{ __('Go to the dashboard') }}</x-button>
    </div>
  @endif
</x-top-bar-layout>

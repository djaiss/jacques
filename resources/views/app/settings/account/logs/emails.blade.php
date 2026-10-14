{{-- Every email we sent to the person signed in, a page at a time. --}}
{{-- @var \App\ViewModels\Settings\Account\Logs\EmailsSentViewModel $viewModel --}}
<x-top-bar-layout :title="__('Emails sent')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Logs') => route('settings.logs.index'),
      __('Emails sent') => null,
    ]"
  />

  <!-- page title -->
  <div class="mb-11">
    <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Emails sent') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('Every email we sent to your account, most recent first.') }}</p>
  </div>

  <!-- list of emails -->
  <x-section :title="__('Every email')" icon="emails" :hue="50" padding="p-0">
    <div id="emails-sent-container" x-merge="append">
      @forelse ($viewModel->emailsSent() as $emailSent)
        @include('app.settings.account.logs._email-sent-row', ['emailSent' => $emailSent])
      @empty
        <x-empty-state :title="__('No emails yet')">
          <x-slot:icon>
            <svg width="22" height="22" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1.5" y="3.5" width="13" height="9" rx="1.5"></rect>
              <path d="m2 4.5 6 4.5 6-4.5"></path>
            </svg>
          </x-slot:icon>

          {{ __('Nothing has left our hands yet. The emails we send you, such as sign-in links and password changes, show up here once they do.') }}
        </x-empty-state>
      @endforelse

      @if ($viewModel->emailsSent()->hasMorePages())
        <div id="pagination" class="border-t border-hairline-soft px-4.5 py-3 text-center text-sm">
          <x-link x-target="emails-sent-container pagination" x-autofocus :href="$viewModel->emailsSent()->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      @endif
    </div>
  </x-section>
</x-top-bar-layout>

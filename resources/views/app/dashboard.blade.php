{{-- Where somebody lands once the salon is set up, a placeholder until it is designed. --}}
<x-top-bar-layout :title="__('Dashboard')">
  @if (session('welcome'))
    <!-- welcome message -->
    <div role="status" class="mb-8 max-w-180 space-y-1 rounded-xl border border-hairline bg-canvas px-5 py-4">
      <p class="text-[17px] font-bold tracking-[-0.01em] text-ink">{{ __('Welcome to :salon!', ['salon' => session('welcome')]) }}</p>
      <p class="text-[15px] leading-normal text-pretty text-body">{{ __('Your account is ready. You can now view your appointments and manage your availability.') }}</p>
    </div>
  @endif

  <!-- page title -->
  <div class="max-w-180 space-y-2">
    <h1 class="text-4xl leading-tight font-bold tracking-[-0.025em] text-ink">{{ __('Dashboard') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('Your salon is ready. This page is coming soon.') }}</p>
  </div>
</x-top-bar-layout>

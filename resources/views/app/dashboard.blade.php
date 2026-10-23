{{-- Where somebody lands once the salon is set up: the day of every hairdresser, invented for now. --}}
{{-- @var \App\ViewModels\Dashboard\DashboardViewModel $viewModel --}}
<x-top-bar-layout :title="__('Dashboard')" :wide="true">
  @if (session('welcome'))
    <!-- welcome message -->
    <div role="status" class="mb-6 max-w-180 space-y-1 rounded-xl border border-hairline bg-canvas px-5 py-4">
      <p class="text-[17px] font-bold tracking-[-0.01em] text-ink">{{ __('Welcome to :salon!', ['salon' => session('welcome')]) }}</p>
      <p class="text-[15px] leading-normal text-pretty text-body">{{ __('Your account is ready. You can now view your appointments and manage your availability.') }}</p>
    </div>
  @endif

  <div x-data="dayAgenda({{ Js::from($viewModel->alpineState()) }})" class="flex flex-col gap-6 lg:flex-row lg:items-start">
    <!-- day choice -->
    <aside class="flex w-full shrink-0 flex-col gap-4 lg:w-68">
      @unless ($viewModel->isClosed())
        <button
          type="button"
          x-on:click="bookFirstSlot()"
          class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-[10px] bg-primary text-[15px] font-medium text-on-primary transition-opacity duration-150 hover:opacity-88 focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:ring-offset-2 focus-visible:outline-none"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M8 3v10M3 8h10"></path>
          </svg>
          {{ __('Book an appointment') }}
        </button>
      @endunless

      @include('app.dashboard._calendar', ['calendar' => $viewModel->calendar()])

      @include('app.dashboard._hairdressers', ['hairdressers' => $viewModel->hairdressers()])
    </aside>

    @include('app.dashboard._day', ['viewModel' => $viewModel])

    @include('app.dashboard._appointment-panel', ['services' => $viewModel->services()])
  </div>
</x-top-bar-layout>

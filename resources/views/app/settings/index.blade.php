{{-- Where somebody goes to change something: their own account first, then the company they work at. --}}
{{-- @var \App\ViewModels\Settings\SettingsViewModel $viewModel --}}
<x-top-bar-layout :title="__('Settings')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => null,
    ]"
  />

  <div class="mb-10 grid items-end gap-8 sm:grid-cols-[minmax(0,1fr)_auto]">
    <!-- page title -->
    <div>
      <h1 class="mb-2 text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Settings') }}</h1>
      <p class="text-lg leading-normal text-pretty text-body">{{ __('Your account, and what you administer for :company.', ['company' => $viewModel->companyName()]) }}</p>
    </div>

    <!-- who you are -->
    <div class="flex items-center gap-3.5 rounded-2xl bg-canvas p-4.5 ring-[1.5px] ring-hairline">
      <x-avatar :staff="$viewModel->staff()" :name="$viewModel->name()" :size="44" />

      <div>
        <p class="font-semibold text-ink">{{ $viewModel->name() }}</p>
        <p class="mt-0.5 text-sm text-muted">{{ $viewModel->role() }}</p>
      </div>
    </div>
  </div>

  <!-- your own settings -->
  <section class="mb-11.5">
    <h2 class="mb-3.5 text-[22px] font-bold tracking-tight text-ink">{{ __('You') }}</h2>

    <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
      @foreach ($viewModel->accountRows() as $row)
        @include('app.settings._row', ['row' => $row])
      @endforeach
    </div>
  </section>

  @php
    $companyRows = $viewModel->companyRows();
  @endphp

  <!-- company settings -->
  @if ($companyRows !== [])
    <section>
      <h2 class="mb-3.5 text-[22px] font-bold tracking-tight text-ink">{{ __('This company') }}</h2>

      <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
        @foreach ($companyRows as $row)
          @include('app.settings._row', ['row' => $row])
        @endforeach
      </div>
    </section>
  @endif
</x-top-bar-layout>

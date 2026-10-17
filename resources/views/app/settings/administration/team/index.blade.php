{{-- The people who work at the salon, and where the invitation to create their account stands. --}}
{{-- @var \App\ViewModels\Settings\Administration\TeamViewModel $viewModel --}}
<x-top-bar-layout :title="__('Team members')">
  <x-breadcrumb
    :trail="[
      __('Dashboard') => route('home.index'),
      __('Settings') => route('settings.index'),
      __('Team members') => null,
    ]"
  />

  <!-- page title -->
  <div class="mb-9 max-w-180 space-y-2">
    <h1 class="text-4xl leading-tight font-bold tracking-tight text-ink">{{ __('Team members') }}</h1>
    <p class="text-lg leading-normal text-pretty text-body">{{ __('An account lets somebody at :salon see their appointments and manage their own availability. Without one, they can still be booked.', ['salon' => $viewModel->companyName()]) }}</p>
  </div>

  @if ($errors->has('invitation'))
    <!-- refusal -->
    <p role="alert" class="mb-6 rounded-xl border border-error/40 bg-canvas px-4.5 py-3.5 text-[15px] leading-normal text-pretty text-error">{{ $errors->first('invitation') }}</p>
  @endif

  <!-- team list -->
  <div class="overflow-hidden rounded-[18px] bg-canvas ring-[1.5px] ring-hairline">
    @foreach ($viewModel->rows() as $row)
      @include('app.settings.administration.team._row', ['row' => $row, 'roleOptions' => $viewModel->roleOptions()])
    @endforeach
  </div>
</x-top-bar-layout>

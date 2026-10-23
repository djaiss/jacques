{{-- The name of a service category, in every language of the salon. --}}
{{-- @var \App\ViewModels\Services\ServiceCategoryFormViewModel $viewModel --}}
<x-form method="{{ $viewModel->method() }}" :action="$viewModel->action()" id="category-form" class="space-y-5 rounded-[18px] bg-canvas p-6 ring-[1.5px] ring-hairline">
  @foreach ($viewModel->names() as $code => $name)
    <x-input
      id="category-name-{{ $code }}"
      name="name[{{ $code }}]"
      :label="count($viewModel->languages()) > 1 ? __('Name (:language)', ['language' => $viewModel->languageLabel($code)]) : __('Name')"
      :value="$name"
      :placeholder="__('Barbering')"
      :required="$code === $viewModel->firstLanguage()"
      :error="$errors->get('name.'.$code)"
      maxlength="255"
    />
  @endforeach

  <div class="flex flex-wrap gap-2.5">
    <x-button.secondary :href="route('services.index')" class="h-10">{{ __('Cancel') }}</x-button.secondary>
    <x-button class="h-10">{{ $viewModel->saveLabel() }}</x-button>
  </div>
</x-form>

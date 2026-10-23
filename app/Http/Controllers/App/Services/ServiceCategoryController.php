<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Services;

use App\Actions\CreateServiceCategory;
use App\Actions\DestroyServiceCategory;
use App\Actions\UpdateServiceCategory;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Services\ServiceCategoryFormViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function new(Request $request): View
    {
        $this->authorizeManage($request);

        return view('app.services.categories.new', [
            'viewModel' => new ServiceCategoryFormViewModel(user: $request->user()),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate($this->rules($request), $this->messages());

        new CreateServiceCategory(
            author: $request->user(),
            company: $request->user()->company,
            name: $validated['name'],
        )->execute();

        return redirect()->route('services.index')
            ->with('status', __('The category is added.'));
    }

    public function edit(Request $request, int $category): View
    {
        $this->authorizeManage($request);

        return view('app.services.categories.edit', [
            'viewModel' => new ServiceCategoryFormViewModel(
                user: $request->user(),
                category: $request->user()->company->serviceCategories()->findOrFail($category),
            ),
        ]);
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        $this->authorizeManage($request);

        $category = $request->user()->company->serviceCategories()->findOrFail($category);
        $validated = $request->validate($this->rules($request), $this->messages());

        new UpdateServiceCategory(
            author: $request->user(),
            category: $category,
            name: $validated['name'],
            description: $category->description ?? [],
        )->execute();

        return redirect()->route('services.index')
            ->with('status', __('The category is saved.'));
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        $this->authorizeManage($request);

        new DestroyServiceCategory(
            author: $request->user(),
            category: $request->user()->company->serviceCategories()->findOrFail($category),
        )->execute();

        return redirect()->route('services.index')
            ->with('status', __('The category is removed.'))
            ->with('status_description', __('Its services stay in the catalog, without a category.'));
    }

    private function authorizeManage(Request $request): void
    {
        $request->user()
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($request->user()->company)
            ->authorize();
    }

    /** @return array<string, mixed> */
    private function rules(Request $request): array
    {
        $first = $request->user()->company->languages[0];

        return [
            'name' => ['required', 'array'],
            "name.{$first}" => ['required', 'string', 'max:255'],
            'name.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.*.required' => __('The category needs a name.'),
        ];
    }
}

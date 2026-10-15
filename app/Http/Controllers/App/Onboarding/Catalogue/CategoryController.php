<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\CreateServiceCategory;
use App\Actions\DestroyServiceCategory;
use App\Actions\UpdateServiceCategory;
use App\Helpers\CatalogueSnapshot;
use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CategoryController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        new CreateServiceCategory(
            author: $request->user(),
            company: $request->user()->company,
            name: [],
        )->execute();

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }

    public function update(Request $request, ServiceCategory $category): RedirectResponse
    {
        abort_unless($category->company_id === $request->user()->company_id, 404);

        $validated = $request->validateWithBag("category_{$category->id}", [
            'name' => ['array'],
            'name.*' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdateServiceCategory(
                author: $request->user(),
                category: $category,
                name: $validated['name'] ?? [],
                description: $category->description ?? [],
            )->execute();
        } catch (InvalidArgumentException) {
            return back()->withErrors(['category' => __('Another category already has this name.')], "category_{$category->id}");
        }

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }

    public function destroy(Request $request, ServiceCategory $category): RedirectResponse
    {
        abort_unless($category->company_id === $request->user()->company_id, 404);

        $snapshot = CatalogueSnapshot::ofCategory($category);
        $name = $category->localizedName(app()->getLocale()) ?: __('Untitled');
        $count = $category->services()->count();

        new DestroyServiceCategory(
            author: $request->user(),
            category: $category,
            withServices: true,
        )->execute();

        $message = $count === 0
            ? __('“:name” removed.', ['name' => $name])
            : trans_choice('“:name” and its :count service removed.|“:name” and its :count services removed.', $count, ['name' => $name]);

        $request->session()->put('onboarding.removal', [
            'message' => $message,
            'snapshot' => $snapshot,
        ]);

        return redirect()->route('onboarding.services.index');
    }
}

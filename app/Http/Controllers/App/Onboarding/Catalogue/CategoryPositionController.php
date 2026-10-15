<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\UpdateServiceCategoryPosition;
use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CategoryPositionController extends Controller
{
    public function update(Request $request, ServiceCategory $category): RedirectResponse
    {
        abort_unless($category->company_id === $request->user()->company_id, 404);

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        try {
            new UpdateServiceCategoryPosition(
                author: $request->user(),
                category: $category,
                position: $validated['direction'] === 'up' ? $category->position - 1 : $category->position + 1,
            )->execute();
        } catch (InvalidArgumentException) {
            return back()->with('error', __('This category cannot move any further.'));
        }

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }
}

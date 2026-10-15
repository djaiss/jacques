<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\CreateService;
use App\Actions\DestroyService;
use App\Actions\UpdateService;
use App\Enums\OnboardingStepEnum;
use App\Helpers\CatalogueSnapshot;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\ViewModels\Onboarding\ServicesViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

class ServiceController extends Controller
{
    public function create(Request $request, ServiceCategory $category): RedirectResponse
    {
        abort_unless($category->company_id === $request->user()->company_id, 404);

        $service = new CreateService(
            author: $request->user(),
            company: $request->user()->company,
            name: [],
            prices: [],
            steps: [['duration' => 30, 'requires_staff' => true]],
            category: $category,
        )->execute();

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.services.edit', $service)->with('catalogue_saved', true);
    }

    public function edit(Request $request, Service $service): View
    {
        abort_unless($service->company_id === $request->user()->company_id, 404);

        return view('app.onboarding.services', [
            'viewModel' => new ServicesViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Services,
                openService: $service,
            ),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->company_id === $request->user()->company_id, 404);

        $request->merge(['prices' => $this->amounts($request)]);

        /*
         * The panel opens without changing the address of the page, so going
         * back would close it and lose the errors.
         */
        $validator = Validator::make($request->all(), [
            'name' => ['array'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'description' => ['array'],
            'description.*' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'integer'],
            'prices' => ['array'],
            'prices.*' => ['nullable', 'decimal:0,2', 'min:0', 'max:1000000'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['array'],
            'steps.*.name.*' => ['nullable', 'string', 'max:255'],
            'steps.*.duration' => ['required', 'integer', 'min:1', 'max:1440'],
            'steps.*.requires_staff' => ['required', 'boolean'],
        ], [
            'prices.*' => __('Enter a price of 0 or more, with at most two decimals.'),
            'steps.*.duration' => __('Enter a duration between 1 and 1440 minutes.'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('onboarding.services.services.edit', $service)->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            $category = filled($validated['category'] ?? null) ? ServiceCategory::query()->findOrFail($validated['category']) : null;
        } catch (ModelNotFoundException) {
            abort(404);
        }

        try {
            new UpdateService(
                author: $request->user(),
                service: $service,
                name: $validated['name'] ?? [],
                prices: array_map(fn (string $amount): int => (int) round((float) $amount * 100), array_filter($validated['prices'] ?? [], fn (?string $amount): bool => $amount !== null)),
                steps: array_map(fn (array $step): array => [
                    'name' => $step['name'] ?? [],
                    'duration' => (int) $step['duration'],
                    'requires_staff' => (bool) $step['requires_staff'],
                ], array_values($validated['steps'])),
                description: $validated['description'] ?? [],
                category: $category,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('onboarding.services.services.edit', $service)->withErrors(['service' => __('Another service already has this name, or no step needs a team member.')]);
        }

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.services.edit', $service)->with('catalogue_saved', true);
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->company_id === $request->user()->company_id, 404);

        $snapshot = CatalogueSnapshot::ofService($service);
        $name = $service->localizedName(app()->getLocale()) ?: __('Untitled service');

        new DestroyService(
            author: $request->user(),
            service: $service,
        )->execute();

        $request->session()->put('onboarding.removal', [
            'message' => __('“:name” removed.', ['name' => $name]),
            'snapshot' => $snapshot,
        ]);

        return redirect()->route('onboarding.services.index');
    }

    /**
     * The amounts as typed, with a decimal comma read as a decimal point and an
     * empty field read as no price.
     *
     * @return array<string, string|null>
     */
    private function amounts(Request $request): array
    {
        return array_map(
            fn (mixed $amount): ?string => blank($amount) ? null : str_replace(',', '.', trim((string) $amount)),
            (array) $request->input('prices', []),
        );
    }
}

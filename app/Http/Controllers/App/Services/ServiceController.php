<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Services;

use App\Actions\CreateService;
use App\Actions\SyncServiceOffers;
use App\Actions\UpdateService;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\ViewModels\Services\ServiceFormViewModel;
use App\ViewModels\Services\ServicesViewModel;
use App\ViewModels\Services\ServiceViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $request->user()->permission(PermissionEnum::ServiceView)->anywhere()->authorize();

        return view('app.services.index', [
            'viewModel' => new ServicesViewModel(
                user: $request->user(),
                search: trim((string) $request->query('q', '')),
            ),
        ]);
    }

    public function new(Request $request): View
    {
        $this->authorizeManage($request);

        return view('app.services.new', [
            'viewModel' => new ServiceFormViewModel(user: $request->user()),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $request->merge(['prices' => $this->amounts($request)]);
        $validated = $request->validate($this->rules($request), $this->messages());

        try {
            $service = DB::transaction(function () use ($request, $validated): Service {
                $service = new CreateService(
                    author: $request->user(),
                    company: $request->user()->company,
                    name: $validated['name'],
                    prices: $this->cents($validated['prices'] ?? []),
                    steps: $this->steps($validated['steps']),
                    description: $validated['description'] ?? [],
                    category: $this->category($request, $validated['category'] ?? null),
                )->execute();

                return new SyncServiceOffers(
                    author: $request->user(),
                    service: $service,
                    offers: $this->offers($validated['offers'] ?? []),
                )->execute();
            });
        } catch (InvalidArgumentException) {
            return back()->withInput()->withErrors(['service' => __('Another service already has this name, no step needs a team member, or somebody ticked does not work at that salon.')]);
        }

        return redirect()->route('services.show', $service->id)
            ->with('status', __('The service is added.'));
    }

    public function show(Request $request, int $service): View
    {
        $request->user()->permission(PermissionEnum::ServiceView)->anywhere()->authorize();

        return view('app.services.show', [
            'viewModel' => new ServiceViewModel(
                user: $request->user(),
                service: $request->user()->company->services()->findOrFail($service),
            ),
        ]);
    }

    public function edit(Request $request, int $service): View
    {
        $this->authorizeManage($request);

        return view('app.services.edit', [
            'viewModel' => new ServiceFormViewModel(
                user: $request->user(),
                service: $request->user()->company->services()->findOrFail($service),
            ),
        ]);
    }

    public function update(Request $request, int $service): RedirectResponse
    {
        $this->authorizeManage($request);

        $service = $request->user()->company->services()->findOrFail($service);

        $request->merge(['prices' => $this->amounts($request)]);
        $validated = $request->validate($this->rules($request), $this->messages());

        try {
            DB::transaction(function () use ($request, $validated, $service): void {
                new UpdateService(
                    author: $request->user(),
                    service: $service,
                    name: $validated['name'],
                    prices: $this->cents($validated['prices'] ?? []),
                    steps: $this->steps($validated['steps']),
                    description: $validated['description'] ?? [],
                    category: $this->category($request, $validated['category'] ?? null),
                )->execute();

                new SyncServiceOffers(
                    author: $request->user(),
                    service: $service,
                    offers: $this->offers($validated['offers'] ?? []),
                )->execute();
            });
        } catch (InvalidArgumentException) {
            return back()->withInput()->withErrors(['service' => __('Another service already has this name, no step needs a team member, or somebody ticked does not work at that salon.')]);
        }

        return redirect()->route('services.show', $service->id)
            ->with('status', __('The service is saved.'));
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
            'offers' => ['array'],
            'offers.*' => ['array'],
            'offers.*.*' => ['nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.*.required' => __('The service needs a name.'),
            'prices.*' => __('Enter a price of 0 or more, with at most two decimals.'),
            'steps.*.duration' => __('Enter a duration between 1 and 1440 minutes.'),
        ];
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

    /**
     * @param  array<string, string|null>  $amounts
     * @return array<string, int>
     */
    private function cents(array $amounts): array
    {
        return array_map(
            fn (string $amount): int => (int) round((float) $amount * 100),
            array_filter($amounts, fn (?string $amount): bool => $amount !== null),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return list<array{name: array<string, string|null>, duration: int, requires_staff: bool}>
     */
    private function steps(array $steps): array
    {
        return array_values(array_map(fn (array $step): array => [
            'name' => $step['name'] ?? [],
            'duration' => (int) $step['duration'],
            'requires_staff' => (bool) $step['requires_staff'],
        ], $steps));
    }

    /**
     * Each ticked salon with the people ticked in it. A ticked salon always sends
     * an empty value, so a salon ticked with nobody in it still arrives.
     *
     * @param  array<int|string, array<int, mixed>>  $offers
     * @return array<int, list<int>>
     */
    private function offers(array $offers): array
    {
        $result = [];

        foreach ($offers as $locationId => $staffIds) {
            $result[(int) $locationId] = array_values(array_map('intval', array_filter($staffIds, fn (mixed $id): bool => filled($id))));
        }

        return $result;
    }

    private function category(Request $request, mixed $categoryId): ?ServiceCategory
    {
        if (blank($categoryId)) {
            return null;
        }

        return $request->user()->company->serviceCategories()->findOrFail((int) $categoryId);
    }
}

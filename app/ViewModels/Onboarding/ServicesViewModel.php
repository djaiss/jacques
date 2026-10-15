<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Helpers\CatalogueText;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ServicesViewModel extends StepViewModel
{
    /** @var Collection<int, ServiceCategory>|null */
    private ?Collection $categories = null;

    /** @var Collection<int, Service>|null */
    private ?Collection $uncategorized = null;

    public function __construct(
        User $user,
        OnboardingStepEnum $step,
        private readonly ?Service $openService = null,
    ) {
        parent::__construct(user: $user, step: $step);
    }

    /** @return list<string> */
    public function languageCodes(): array
    {
        return $this->user->company->languages;
    }

    public function firstLanguage(): string
    {
        return $this->user->company->languages[0];
    }

    public function hasSeveralLanguages(): bool
    {
        return count($this->user->company->languages) > 1;
    }

    public function languages(): CatalogueLanguages
    {
        return new CatalogueLanguages(
            languages: $this->user->company->languages,
            texts: $this->allTexts(),
        );
    }

    /**
     * The currencies of the salon, each with a symbol nobody can mistake for
     * another currency's, since a salon may price in two kinds of dollars.
     *
     * @return list<array{code: string, symbol: string, name: string}>
     */
    public function currencies(): array
    {
        $currencies = [
            'CAD' => ['symbol' => 'CA$', 'name' => __('Canadian dollar')],
            'USD' => ['symbol' => 'US$', 'name' => __('US dollar')],
            'EUR' => ['symbol' => '€', 'name' => __('Euro')],
            'GBP' => ['symbol' => '£', 'name' => __('Pound sterling')],
            'CHF' => ['symbol' => 'CHF', 'name' => __('Swiss franc')],
        ];

        return array_map(fn (string $code): array => [
            'code' => $code,
            'symbol' => $currencies[$code]['symbol'] ?? $code,
            'name' => $currencies[$code]['name'] ?? $code,
        ], $this->user->company->currencies);
    }

    public function hasCatalogue(): bool
    {
        return $this->loadedCategories()->isNotEmpty() || $this->loadedUncategorized()->isNotEmpty();
    }

    public function serviceCount(): int
    {
        return $this->loadedCategories()->sum(fn (ServiceCategory $category): int => $category->services->count())
            + $this->loadedUncategorized()->count();
    }

    public function summary(): string
    {
        if (! $this->hasCatalogue()) {
            return __('Start from scratch, or from our examples.');
        }

        return trans_choice(':count service in :categories. Keep what you offer, remove the rest.|:count services in :categories. Keep what you offer, remove the rest.', $this->serviceCount(), [
            'categories' => trans_choice(':count category|:count categories', $this->loadedCategories()->count()),
        ]);
    }

    public function missingPriceSummary(): string
    {
        $count = $this->allServices()->filter(fn (Service $service): bool => $service->missingCurrencies() !== [])->count();

        if ($count === 0) {
            return '';
        }

        return trans_choice(':count service missing a price in at least one currency|:count services missing a price in at least one currency', $count);
    }

    public function footerStatus(): string
    {
        if ($this->serviceCount() === 0) {
            return __('No services yet. You can add them later from Settings.');
        }

        return trans_choice(':count service in your catalog|:count services in your catalog', $this->serviceCount());
    }

    public function isSaved(): bool
    {
        return (bool) session('catalogue_saved', false);
    }

    public function removalMessage(): ?string
    {
        return session('onboarding.removal.message');
    }

    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        $categories = $this->loadedCategories();
        $lastIndex = $categories->count() - 1;

        return $categories->values()->map(fn (ServiceCategory $category, int $index): array => [
            'id' => $category->id,
            'texts' => $this->texts($category->name ?? [], __('Untitled category')),
            'serviceCount' => $category->services->count(),
            'serviceCountLabel' => trans_choice(':count service|:count services', $category->services->count()),
            'missing' => $this->missingCounts([$category->name ?? [], ...$category->services->flatMap(fn (Service $service): array => $service->translatableTexts())->all()]),
            'hue' => [30, 80, 200, 250, 310][$index % 5],
            'isFirst' => $index === 0,
            'isLast' => $index === $lastIndex,
            'services' => $category->services->map($this->service(...))->all(),
            'updateUrl' => route('onboarding.services.categories.update', $category),
            'destroyUrl' => route('onboarding.services.categories.destroy', $category),
            'positionUrl' => route('onboarding.services.categoryPositions.update', $category),
            'createServiceUrl' => route('onboarding.services.services.create', $category),
        ])->all();
    }

    /** @return list<array<string, mixed>> */
    public function uncategorizedServices(): array
    {
        return $this->loadedUncategorized()->map($this->service(...))->values()->all();
    }

    public function panel(): ?ServicePanel
    {
        if ($this->openService === null) {
            return null;
        }

        $service = $this->allServices()->firstWhere('id', $this->openService->id);

        if ($service === null) {
            return null;
        }

        return new ServicePanel(
            service: $service,
            categories: $this->loadedCategories(),
            languages: $this->user->company->languages,
            currencies: $this->currencies(),
        );
    }

    /**
     * Each language of the salon with the text written in it, the text a reader
     * sees when it is missing, and whether it is missing.
     *
     * @param  array<string, string|null>  $texts
     * @return array<string, array{value: string, shown: string, isMissing: bool}>
     */
    private function texts(array $texts, string $untitled): array
    {
        $fallback = CatalogueText::resolve($texts, $this->user->company->languages, $this->firstLanguage());
        $result = [];

        foreach ($this->user->company->languages as $language) {
            $value = (string) ($texts[$language] ?? '');

            $result[$language] = [
                'value' => $value,
                'shown' => $value !== '' ? $value : ($fallback !== '' ? $fallback : $untitled),
                'isMissing' => $value === '',
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function service(Service $service): array
    {
        $missingCurrencies = $service->missingCurrencies();
        $symbols = collect($this->currencies())->whereIn('code', $missingCurrencies)->pluck('symbol')->all();

        return [
            'id' => $service->id,
            'texts' => $this->texts($service->name ?? [], __('Untitled service')),
            'missing' => $this->missingCounts($service->translatableTexts()),
            'duration' => $service->duration,
            'isMultiStep' => $service->steps->count() > 1,
            'stepCountLabel' => trans_choice(':count step|:count steps', $service->steps->count()),
            'prices' => array_map(fn (array $currency): array => [
                ...$currency,
                'amount' => $this->amount($service->prices[$currency['code']] ?? null),
                'isMissing' => in_array($currency['code'], $missingCurrencies, true),
            ], $this->currencies()),
            'missingPriceLabel' => match (true) {
                $missingCurrencies === [] => '',
                count($missingCurrencies) === count($this->user->company->currencies) => __('No price'),
                default => __('No :currencies price', ['currencies' => implode(', ', $symbols)]),
            },
            'editUrl' => route('onboarding.services.services.edit', $service),
            'destroyUrl' => route('onboarding.services.services.destroy', $service),
            'durationUrl' => route('onboarding.services.serviceDurations.update', $service),
            'pricesUrl' => route('onboarding.services.servicePrices.update', $service),
        ];
    }

    /**
     * How many of the texts are missing in each language of the salon.
     *
     * @param  list<array<string, string|null>>  $texts
     * @return array<string, int>
     */
    private function missingCounts(array $texts): array
    {
        $counts = [];

        foreach ($this->user->company->languages as $language) {
            $counts[$language] = count(array_filter($texts, fn (array $text): bool => ($text[$language] ?? '') === ''));
        }

        return $counts;
    }

    private function amount(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        if ($cents % 100 === 0) {
            return (string) intdiv($cents, 100);
        }

        return number_format($cents / 100, 2, '.', '');
    }

    /** @return list<array<string, string|null>> */
    private function allTexts(): array
    {
        $texts = [];

        foreach ($this->loadedCategories() as $category) {
            $texts[] = $category->name ?? [];
        }

        foreach ($this->allServices() as $service) {
            $texts = [...$texts, ...$service->translatableTexts()];
        }

        return $texts;
    }

    /** @return SupportCollection<int, Service> */
    private function allServices(): SupportCollection
    {
        return $this->loadedCategories()
            ->toBase()
            ->flatMap(fn (ServiceCategory $category): array => $category->services->all())
            ->merge($this->loadedUncategorized()->all());
    }

    /** @return Collection<int, ServiceCategory> */
    private function loadedCategories(): Collection
    {
        return $this->categories ??= ServiceCategory::query()
            ->where('company_id', $this->user->company_id)
            ->with(['services.steps', 'services.company', 'company'])
            ->orderBy('position')
            ->get();
    }

    /** @return Collection<int, Service> */
    private function loadedUncategorized(): Collection
    {
        return $this->uncategorized ??= Service::query()
            ->where('company_id', $this->user->company_id)
            ->whereNull('service_category_id')
            ->with(['steps', 'company'])
            ->orderBy('position')
            ->get();
    }
}

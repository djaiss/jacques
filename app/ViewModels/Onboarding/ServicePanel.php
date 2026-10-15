<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Helpers\DefaultCatalogue;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use Illuminate\Database\Eloquent\Collection;

class ServicePanel
{
    /**
     * @param  Collection<int, ServiceCategory>  $categories
     * @param  list<string>  $languages
     * @param  list<array{code: string, symbol: string, name: string}>  $currencies
     */
    public function __construct(
        private readonly Service $service,
        private readonly Collection $categories,
        private readonly array $languages,
        private readonly array $currencies,
    ) {}

    public function id(): int
    {
        return $this->service->id;
    }

    public function title(): string
    {
        return $this->service->localizedName(app()->getLocale()) ?: __('New service');
    }

    public function eyebrow(): string
    {
        $category = $this->service->category?->localizedName(app()->getLocale());

        return __('Edit service · :category', ['category' => $category ?: __('Untitled category')]);
    }

    /** @return array<string, string> */
    public function names(): array
    {
        return $this->inEveryLanguage($this->service->name ?? []);
    }

    /** @return array<string, string> */
    public function descriptions(): array
    {
        return $this->inEveryLanguage($this->service->description ?? []);
    }

    /**
     * What is left to write in each language of the salon, as a sentence.
     *
     * @return array<string, string>
     */
    public function translationStatus(): array
    {
        $status = [];

        foreach ($this->languages as $language) {
            $label = config("jacques.content_languages.{$language}.label", $language);
            $missing = count(array_filter($this->service->translatableTexts(), fn (array $texts): bool => ($texts[$language] ?? '') === ''));

            $status[$language] = $missing === 0
                ? __('Everything is translated in :language', ['language' => $label])
                : trans_choice(':count to translate in :language|:count to translate in :language', $missing, ['language' => $label]);
        }

        return $status;
    }

    public function firstLanguage(): string
    {
        return $this->languages[0];
    }

    public function categoryId(): ?int
    {
        return $this->service->service_category_id;
    }

    /** @return list<array{id: int, label: string}> */
    public function categoryOptions(): array
    {
        return $this->categories->map(fn (ServiceCategory $category): array => [
            'id' => $category->id,
            'label' => $category->localizedName(app()->getLocale()) ?: __('Untitled category'),
        ])->values()->all();
    }

    /** @return list<array{code: string, symbol: string, name: string, amount: string, isMissing: bool}> */
    public function prices(): array
    {
        return array_map(function (array $currency): array {
            $cents = $this->service->prices[$currency['code']] ?? null;

            return [
                ...$currency,
                'amount' => $cents === null ? '' : ($cents % 100 === 0 ? (string) intdiv($cents, 100) : number_format($cents / 100, 2, '.', '')),
                'isMissing' => $cents === null,
            ];
        }, $this->currencies);
    }

    public function priceStatus(): string
    {
        $missing = count($this->service->missingCurrencies());

        if ($missing === 0) {
            return __('Set in every currency');
        }

        return trans_choice(':count of :total missing|:count of :total missing', $missing, ['total' => count($this->currencies)]);
    }

    public function hasEveryPrice(): bool
    {
        return $this->service->missingCurrencies() === [];
    }

    /** @return list<array{name: array<string, string>, duration: int, requires_staff: bool}> */
    public function steps(): array
    {
        return $this->service->steps->map(fn (ServiceStep $step): array => [
            'name' => $this->inEveryLanguage($step->name ?? []),
            'duration' => $step->duration,
            'requires_staff' => $step->requires_staff,
        ])->values()->all();
    }

    /**
     * The name a processing step gets when a service is broken into steps, in
     * every language of the salon the product has a translation for.
     *
     * @return array<string, string>
     */
    public function processingName(): array
    {
        $texts = DefaultCatalogue::texts(fn (string $locale): string => __('Processing', locale: $locale));

        return $this->inEveryLanguage(array_intersect_key($texts, array_flip($this->languages)));
    }

    public function updateUrl(): string
    {
        return route('onboarding.services.services.update', $this->service);
    }

    public function destroyUrl(): string
    {
        return route('onboarding.services.services.destroy', $this->service);
    }

    /**
     * @param  array<string, string|null>  $texts
     * @return array<string, string>
     */
    private function inEveryLanguage(array $texts): array
    {
        $result = [];

        foreach ($this->languages as $language) {
            $result[$language] = (string) ($texts[$language] ?? '');
        }

        return $result;
    }
}

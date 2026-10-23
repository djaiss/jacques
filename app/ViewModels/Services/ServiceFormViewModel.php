<?php

declare(strict_types=1);

namespace App\ViewModels\Services;

use App\Helpers\CurrencySymbols;
use App\Helpers\DefaultCatalogue;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\Staff;
use App\Models\User;

class ServiceFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Service $service = null,
        private readonly ?ServiceCategory $category = null,
    ) {}

    public function isNew(): bool
    {
        return $this->service === null;
    }

    public function eyebrow(): string
    {
        return $this->isNew() ? __('Add a service') : __('Edit service');
    }

    public function title(): string
    {
        if ($this->service === null) {
            return __('New service');
        }

        return $this->service->localizedName(app()->getLocale()) ?: __('Untitled service');
    }

    public function saveLabel(): string
    {
        return $this->isNew() ? __('Add this service') : __('Save changes');
    }

    public function action(): string
    {
        return $this->service === null
            ? route('services.create')
            : route('services.update', $this->service->id);
    }

    public function method(): string
    {
        return $this->isNew() ? 'post' : 'put';
    }

    public function cancelUrl(): string
    {
        return $this->service === null
            ? route('services.index')
            : route('services.show', $this->service->id);
    }

    /** @return array<string, string|null> */
    public function breadcrumb(): array
    {
        if ($this->service === null) {
            return [
                $this->user->company->name => route('dashboard.index'),
                __('Services') => route('services.index'),
                __('New service') => null,
            ];
        }

        return [
            $this->user->company->name => route('dashboard.index'),
            __('Services') => route('services.index'),
            $this->title() => route('services.show', $this->service->id),
            __('Edit') => null,
        ];
    }

    /** @return list<string> */
    public function languages(): array
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

    public function languageLabel(string $language): string
    {
        return (string) config("jacques.content_languages.{$language}.label", $language);
    }

    public function languageFlag(string $language): string
    {
        return (string) config("jacques.content_languages.{$language}.flag", '');
    }

    /** @return array<string, string> */
    public function names(): array
    {
        return $this->inEveryLanguage((array) old('name', $this->service->name ?? []));
    }

    /** @return array<string, string> */
    public function descriptions(): array
    {
        return $this->inEveryLanguage((array) old('description', $this->service->description ?? []));
    }

    public function categoryId(): ?int
    {
        $saved = $this->service !== null ? $this->service->service_category_id : $this->category?->id;
        $categoryId = old('category', $saved === null ? null : (string) $saved);

        return blank($categoryId) ? null : (int) $categoryId;
    }

    /** @return list<array{id: int, label: string}> */
    public function categoryOptions(): array
    {
        return $this->user->company->serviceCategories()
            ->orderBy('position')
            ->get()
            ->map(fn (ServiceCategory $category): array => [
                'id' => $category->id,
                'label' => $category->localizedName(app()->getLocale()) ?: __('Untitled category'),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{code: string, symbol: string, name: string, amount: string}> */
    public function prices(): array
    {
        $typed = (array) old('prices', []);

        return array_map(function (array $currency) use ($typed): array {
            if (array_key_exists($currency['code'], $typed)) {
                return [...$currency, 'amount' => (string) $typed[$currency['code']]];
            }

            $cents = $this->service?->prices[$currency['code']] ?? null;

            return [
                ...$currency,
                'amount' => $cents === null ? '' : ($cents % 100 === 0 ? (string) intdiv($cents, 100) : number_format($cents / 100, 2, '.', '')),
            ];
        }, CurrencySymbols::describe($this->user->company->currencies));
    }

    /** @return list<array{name: array<string, string>, duration: int|string, requires_staff: bool}> */
    public function steps(): array
    {
        $typed = old('steps');

        if (is_array($typed) && $typed !== []) {
            return array_values(array_map(fn (array $step): array => [
                'name' => $this->inEveryLanguage((array) ($step['name'] ?? [])),
                'duration' => (string) ($step['duration'] ?? ''),
                'requires_staff' => (bool) ($step['requires_staff'] ?? true),
            ], $typed));
        }

        if ($this->service === null) {
            return [['name' => $this->inEveryLanguage([]), 'duration' => 30, 'requires_staff' => true]];
        }

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

        return $this->inEveryLanguage(array_intersect_key($texts, array_flip($this->languages())));
    }

    /** @return list<array{id: int, name: string, hue: int, isHeadquarters: bool, isOn: bool, people: list<array{id: int, name: string, staff: Staff, isOn: bool}>}> */
    public function salons(): array
    {
        $typed = old('offers');
        $hasTyped = old('name') !== null;
        $salons = [];

        $locations = $this->user->company->locations()
            ->whereNull('archived_at')
            ->orderByDesc('is_headquarters')
            ->orderBy('name')
            ->with(['staff' => fn ($query) => $query->wherePivot('is_active', true)->where('staff.is_active', true)])
            ->get();

        foreach ($locations->values() as $index => $location) {
            $offer = $this->offerAt($location);
            $picked = $hasTyped
                ? array_map('intval', (array) (($typed ?? [])[$location->id] ?? []))
                : ($offer === null ? [] : $offer->staff->pluck('id')->all());
            $isOn = $hasTyped ? is_array($typed) && array_key_exists($location->id, $typed) : $offer !== null;

            $salons[] = [
                'id' => $location->id,
                'name' => $location->name,
                'hue' => ServicesViewModel::HUES[$index % count(ServicesViewModel::HUES)],
                'isHeadquarters' => $location->is_headquarters,
                'isOn' => $isOn,
                'people' => $location->staff
                    ->sortBy(fn (Staff $staff): string => mb_strtolower($staff->name))
                    ->map(fn (Staff $staff): array => [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'staff' => $staff,
                        'isOn' => in_array($staff->id, $picked, true),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return $salons;
    }

    public function isArchived(): bool
    {
        return $this->service !== null && ! $this->service->is_active;
    }

    public function archiveUrl(): ?string
    {
        return $this->service === null ? null : route('serviceArchives.create', $this->service->id);
    }

    private function offerAt(Location $location): ?LocationService
    {
        if ($this->service === null) {
            return null;
        }

        return $this->service->locationServices()
            ->where('location_id', $location->id)
            ->where('is_active', true)
            ->with(['staff' => fn ($query) => $query->where('staff.is_active', true)])
            ->first();
    }

    /**
     * @param  array<string, string|null>  $texts
     * @return array<string, string>
     */
    private function inEveryLanguage(array $texts): array
    {
        $result = [];

        foreach ($this->languages() as $language) {
            $result[$language] = (string) ($texts[$language] ?? '');
        }

        return $result;
    }
}

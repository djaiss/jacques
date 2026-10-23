<?php

declare(strict_types=1);

namespace App\ViewModels\Services;

use App\Enums\PermissionEnum;
use App\Helpers\CatalogueText;
use App\Helpers\CurrencySymbols;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

class ServicesViewModel
{
    public const array HUES = [30, 80, 200, 250, 310];

    private const int VISIBLE_PEOPLE = 5;

    /** @var Collection<int, ServiceCategory>|null */
    private ?Collection $categories = null;

    /** @var Collection<int, Service>|null */
    private ?Collection $uncategorized = null;

    /** @var Collection<int, Location>|null */
    private ?Collection $locations = null;

    public function __construct(
        private readonly User $user,
        private readonly ?Location $location = null,
        private readonly string $search = '',
    ) {}

    /** @return array<string, string|null> */
    public function breadcrumb(): array
    {
        if ($this->location === null) {
            return [
                $this->user->company->name => route('dashboard.index'),
                __('Services') => null,
            ];
        }

        return [
            $this->user->company->name => route('dashboard.index'),
            __('Services') => route('services.index'),
            $this->location->name => null,
        ];
    }

    public function lead(): string
    {
        if ($this->isEmpty()) {
            return __('Nothing for clients to book yet.');
        }

        $services = $this->allServices();

        if ($this->location === null) {
            return trans_choice(':count service in :categories, offered across :salons.|:count services in :categories, offered across :salons.', $services->count(), [
                'categories' => trans_choice(':count category|:count categories', $this->loadedCategories()->count()),
                'salons' => trans_choice(':count salon|:count salons', $this->loadedLocations()->count()),
            ]);
        }

        $offered = $services->filter(fn (Service $service): bool => $this->offerAt($service, $this->location) !== null);
        $people = $offered->flatMap(fn (Service $service): array => $this->offerAt($service, $this->location)->staff->pluck('id')->all())->unique();

        return trans_choice(':count of :total services is offered at :salon, by :people.|:count of :total services are offered at :salon, by :people.', $offered->count(), [
            'total' => $services->count(),
            'salon' => $this->location->name,
            'people' => trans_choice(':count person|:count people', $people->count()),
        ]);
    }

    /** @return list<array{label: string, text: string, url: string, isCurrent: bool}> */
    public function salons(): array
    {
        $services = $this->allServices();
        $salons = [[
            'label' => __('All salons'),
            'text' => $this->countsText(
                $services->count(),
                $this->loadedLocations()->flatMap(fn (Location $location): array => $location->staff->pluck('id')->all())->unique()->count(),
            ),
            'url' => $this->url(route('services.index')),
            'isCurrent' => $this->location === null,
        ]];

        foreach ($this->loadedLocations() as $location) {
            $salons[] = [
                'label' => $location->name,
                'text' => $this->countsText(
                    $services->filter(fn (Service $service): bool => $this->offerAt($service, $location) !== null)->count(),
                    $location->staff->count(),
                ),
                'url' => $this->url(route('serviceLocations.index', $location->id)),
                'isCurrent' => $this->location?->id === $location->id,
            ];
        }

        return $salons;
    }

    /** @return list<array{key: string, name: string, hue: int, count: int, editUrl: string|null, addUrl: string|null, rows: list<array<string, mixed>>}> */
    public function categories(): array
    {
        $categories = [];

        foreach ($this->loadedCategories()->values() as $index => $category) {
            $rows = $this->rows($category->services);

            if ($rows === [] && $this->search !== '') {
                continue;
            }

            $categories[] = [
                'key' => "category-{$category->id}",
                'name' => $category->localizedName(app()->getLocale()) ?: __('Untitled category'),
                'hue' => self::HUES[$index % count(self::HUES)],
                'count' => count($rows),
                'editUrl' => $this->canManage() ? route('serviceCategories.edit', $category->id) : null,
                'addUrl' => $this->canManage() ? route('categoryServices.new', $category->id) : null,
                'rows' => $rows,
            ];
        }

        $uncategorized = $this->rows($this->loadedUncategorized());

        if ($uncategorized !== []) {
            $categories[] = [
                'key' => 'category-none',
                'name' => __('Without a category'),
                'hue' => 0,
                'count' => count($uncategorized),
                'editUrl' => null,
                'addUrl' => null,
                'rows' => $uncategorized,
            ];
        }

        return $categories;
    }

    public function peopleHeading(): string
    {
        return $this->location === null
            ? __('People')
            : __('People at :salon', ['salon' => $this->location->name]);
    }

    public function isEmpty(): bool
    {
        return $this->allServices()->isEmpty() && $this->loadedCategories()->isEmpty();
    }

    public function canLoadReadyMadeCatalogue(): bool
    {
        return $this->isEmpty()
            && $this->canManage()
            && ! $this->user->company->services()->exists();
    }

    public function readyMadeCatalogueUrl(): string
    {
        return route('serviceCatalogues.create');
    }

    public function noResults(): bool
    {
        return $this->search !== '' && $this->categories() === [];
    }

    public function search(): string
    {
        return $this->search;
    }

    public function searchUrl(): string
    {
        return $this->location === null
            ? route('services.index')
            : route('serviceLocations.index', $this->location->id);
    }

    public function canManage(): bool
    {
        return $this->user
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    public function newServiceUrl(): string
    {
        return route('services.new');
    }

    public function newCategoryUrl(): string
    {
        return route('serviceCategories.new');
    }

    /**
     * @param  Collection<int, Service>  $services
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $services): array
    {
        $needle = mb_strtolower($this->search);
        $rows = [];

        foreach ($services as $service) {
            if ($needle !== '' && ! str_contains(mb_strtolower(implode(' ', array_filter($service->name ?? []))), $needle)) {
                continue;
            }

            $rows[] = $this->row($service);
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function row(Service $service): array
    {
        $offers = $this->offersOf($service);
        $current = $this->location === null ? null : $this->offerAt($service, $this->location);
        $isOffered = $this->location === null ? $offers->isNotEmpty() : $current !== null;
        $people = $this->location === null
            ? $offers->flatMap(fn (LocationService $offer): array => $offer->staff->all())->unique('id')->values()
            : ($current === null ? new Collection : $current->staff);
        $name = CatalogueText::resolve($service->name ?? [], $this->user->company->languages, app()->getLocale());

        return [
            'id' => $service->id,
            'name' => $name !== '' ? $name : __('Untitled service'),
            'isUntitled' => $name === '',
            'steps' => $service->steps->count() > 1 ? trans_choice(':count step|:count steps', $service->steps->count()) : '',
            'duration' => __(':minutes min', ['minutes' => $service->duration]),
            'prices' => $this->prices($service),
            'salons' => $this->salonsOf($offers),
            'isDimmed' => $this->location !== null && ! $isOffered,
            'notOfferedHere' => $this->location !== null && ! $isOffered ? __('Not offered at :salon', ['salon' => $this->location->name]) : '',
            'nobody' => $isOffered && $people->isEmpty(),
            'people' => $people->take(self::VISIBLE_PEOPLE)->map(fn (Staff $staff): array => ['staff' => $staff, 'name' => $staff->name])->all(),
            'peopleNames' => $people->pluck('name')->implode(', '),
            'more' => max(0, $people->count() - self::VISIBLE_PEOPLE),
            'url' => route('services.show', $service->id),
        ];
    }

    /** @return list<array{symbol: string, amount: string}> */
    private function prices(Service $service): array
    {
        return array_map(fn (array $currency): array => [
            'symbol' => $currency['symbol'],
            'amount' => isset($service->prices[$currency['code']])
                ? Number::format($service->prices[$currency['code']] / 100, maxPrecision: 2, locale: app()->getLocale())
                : '',
        ], CurrencySymbols::describe($this->user->company->currencies));
    }

    /**
     * @param  Collection<int, LocationService>  $offers
     * @return array{label: string, names: string, isWarning: bool}
     */
    private function salonsOf(Collection $offers): array
    {
        $names = $offers->map(fn (LocationService $offer): string => $offer->location->name)->implode(', ');

        if ($offers->count() === 0) {
            return ['label' => __('Not offered anywhere yet'), 'names' => '', 'isWarning' => true];
        }

        if ($offers->count() === $this->loadedLocations()->count()) {
            return ['label' => trans_choice('{1}The only salon|All :count salons', $offers->count()), 'names' => $names, 'isWarning' => false];
        }

        return ['label' => trans_choice(':count salon|:count salons', $offers->count()), 'names' => $names, 'isWarning' => false];
    }

    private function countsText(int $services, int $people): string
    {
        return __(':services, :people', [
            'services' => trans_choice(':count service|:count services', $services),
            'people' => trans_choice(':count person|:count people', $people),
        ]);
    }

    /** @return Collection<int, LocationService> */
    private function offersOf(Service $service): Collection
    {
        $locationIds = $this->loadedLocations()->pluck('id')->all();

        return $service->locationServices
            ->filter(fn (LocationService $offer): bool => $offer->is_active && in_array($offer->location_id, $locationIds, true))
            ->values();
    }

    private function offerAt(Service $service, Location $location): ?LocationService
    {
        return $this->offersOf($service)->firstWhere('location_id', $location->id);
    }

    /** @return Collection<int, Service> */
    private function allServices(): Collection
    {
        return new Collection([
            ...$this->loadedCategories()->flatMap(fn (ServiceCategory $category): array => $category->services->all())->all(),
            ...$this->loadedUncategorized()->all(),
        ]);
    }

    /** @return Collection<int, ServiceCategory> */
    private function loadedCategories(): Collection
    {
        return $this->categories ??= $this->user->company->serviceCategories()
            ->orderBy('position')
            ->with(['services' => fn ($query) => $this->eagerLoad($query)])
            ->get();
    }

    /** @return Collection<int, Service> */
    private function loadedUncategorized(): Collection
    {
        return $this->uncategorized ??= $this->eagerLoad(
            $this->user->company->services()->whereNull('service_category_id'),
        )->get();
    }

    /** @return Collection<int, Location> */
    private function loadedLocations(): Collection
    {
        return $this->locations ??= $this->user->company->locations()
            ->whereNull('archived_at')
            ->orderByDesc('is_headquarters')
            ->orderBy('name')
            ->with(['staff' => fn ($query) => $query->wherePivot('is_active', true)->where('staff.is_active', true)])
            ->get();
    }

    /**
     * @template TQuery of Builder<Service>|HasMany<Service, *>
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    private function eagerLoad(Builder|HasMany $query): Builder|HasMany
    {
        return $query
            ->where('is_active', true)
            ->orderBy('position')
            ->with([
                'steps',
                'locationServices.location',
                'locationServices.staff' => fn ($staff) => $staff->where('staff.is_active', true),
            ]);
    }

    private function url(string $url): string
    {
        return $this->search === '' ? $url : $url.'?'.http_build_query(['q' => $this->search]);
    }
}

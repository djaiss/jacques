<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Administration;

use App\Enums\LocationScopeEnum;
use App\Enums\PermissionEnum;
use App\Models\Location;
use App\Models\Staff;
use App\Models\User;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

class LocationsViewModel
{
    /** @var Collection<int, Location>|null */
    private ?Collection $locations = null;

    /** @var Collection<int, Staff>|null */
    private ?Collection $people = null;

    public function __construct(
        private readonly User $user,
        private readonly LocationScopeEnum $scope,
        private readonly string $search = '',
        private readonly string $sort = 'name',
    ) {}

    /** @return array<int, array{value: int, label: string, icon: string, hue: int}> */
    public function stats(): array
    {
        $open = $this->locations()->reject(fn (Location $location): bool => $location->isArchived());

        return [
            [
                'value' => $open->count(),
                'label' => __('open locations'),
                'icon' => 'locations',
                'hue' => 80,
            ],
            [
                'value' => $open->pluck('country')->filter()->unique()->count(),
                'label' => __('countries'),
                'icon' => 'countries',
                'hue' => 150,
            ],
            [
                'value' => $open->pluck('timezone')->filter()->unique()->count(),
                'label' => __('time zones'),
                'icon' => 'time-zones',
                'hue' => 250,
            ],
        ];
    }

    /** @return array<int, array{label: string, url: string, current: bool}> */
    public function scopes(): array
    {
        return array_map(fn (LocationScopeEnum $scope): array => [
            'label' => __($scope->label()),
            'url' => $this->url($scope),
            'current' => $scope === $this->scope,
        ], LocationScopeEnum::cases());
    }

    /** @return array<int, array{id: int, name: string, badge: string, place: string, timezone: string, isHeadquarters: bool, isArchived: bool}> */
    public function rows(): array
    {
        return array_values(array_map(fn (Location $location): array => [
            'id' => $location->id,
            'name' => $location->name,
            'badge' => $this->badge($location),
            'place' => $this->place($location),
            'timezone' => $location->timezone ?? __('same as the company'),
            'isHeadquarters' => $location->is_headquarters,
            'isArchived' => $location->isArchived(),
        ], $this->filtered()->all()));
    }

    /** @return array<int, array{id: int, name: string, country: string, region: string, city: string, postalCode: string, addressLine1: string, addressLine2: string, phone: string, email: string, timezone: string, isHeadquarters: bool, isArchived: bool, updateUrl: string, archiveUrl: string, restoreUrl: string, inheritNote: string, assignUrl: string, people: array<int, array{id: int, name: string, removeUrl: string}>, assignable: array<int, array{id: int, name: string}>}> */
    public function drawer(): array
    {
        $drawer = [];

        foreach ($this->filtered() as $location) {
            $drawer[$location->id] = [
                'id' => $location->id,
                'name' => $location->name,
                'country' => $location->country ?? '',
                'region' => $location->region ?? '',
                'city' => $location->city ?? '',
                'postalCode' => $location->postal_code ?? '',
                'addressLine1' => $location->address_line_1 ?? '',
                'addressLine2' => $location->address_line_2 ?? '',
                'phone' => $location->phone ?? '',
                'email' => $location->email ?? '',
                'timezone' => $location->timezone ?? '',
                'isHeadquarters' => $location->is_headquarters,
                'isArchived' => $location->isArchived(),
                'updateUrl' => route('settings.locations.update', $location->id),
                'archiveUrl' => route('settings.locationArchives.create', $location->id),
                'restoreUrl' => route('settings.locationArchives.destroy', $location->id),
                'inheritNote' => $this->inheritNote($location),
                'assignUrl' => route('settings.locationPeople.create', $location->id),
                'people' => $this->peopleOf($location),
                'assignable' => $this->assignableTo($location),
            ];
        }

        return $drawer;
    }

    public function openLocationId(): ?int
    {
        $id = old('location_id');

        return $id === null ? null : (int) $id;
    }

    /** @return array{name: string, country: string, region: string, city: string, postalCode: string, addressLine1: string, addressLine2: string, phone: string, email: string, timezone: string, isHeadquarters: bool} */
    public function openLocationForm(): array
    {
        return [
            'name' => (string) old('name', ''),
            'country' => (string) old('country', ''),
            'region' => (string) old('region', ''),
            'city' => (string) old('city', ''),
            'postalCode' => (string) old('postal_code', ''),
            'addressLine1' => (string) old('address_line_1', ''),
            'addressLine2' => (string) old('address_line_2', ''),
            'phone' => (string) old('phone', ''),
            'email' => (string) old('email', ''),
            'timezone' => (string) old('timezone', ''),
            'isHeadquarters' => old('is_headquarters') !== null,
        ];
    }

    /** @return array<int, string> */
    public function timezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    public function search(): string
    {
        return $this->search;
    }

    /** @return array<string, string> */
    public function sortState(): array
    {
        return ['sort' => $this->sort];
    }

    /** @return array{label: string, description: string, url: string} */
    public function sortToggle(): array
    {
        $byName = $this->sort === 'name';

        return [
            'label' => $byName ? __('Sorted by location') : __('Sorted by city'),
            'description' => $byName
                ? __('Sorted by location. Sort by city instead.')
                : __('Sorted by city. Sort by location instead.'),
            'url' => $this->url($this->scope, ['sort' => $byName ? 'place' : '']),
        ];
    }

    public function createUrl(): string
    {
        return route('settings.locations.create');
    }

    public function canAssignPeople(): bool
    {
        return $this->user
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    public function canAddLocations(): bool
    {
        return $this->user
            ->permission(PermissionEnum::LocationManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    public function companyHasNoLocation(): bool
    {
        return $this->locations()->isEmpty();
    }

    /** @return array<int, array{id: int, name: string, removeUrl: string}> */
    private function peopleOf(Location $location): array
    {
        if (! $this->canAssignPeople()) {
            return [];
        }

        return $this->people()
            ->filter(fn (Staff $staff): bool => $staff->locations->contains('id', $location->id))
            ->map(fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => $staff->name,
                'removeUrl' => route('settings.locationPeople.destroy', [$location->id, $staff->id]),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function assignableTo(Location $location): array
    {
        if (! $this->canAssignPeople()) {
            return [];
        }

        return $this->people()
            ->reject(fn (Staff $staff): bool => $staff->locations->contains('id', $location->id))
            ->map(fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => $staff->name,
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, Staff> */
    private function people(): Collection
    {
        return $this->people ??= $this->user->company->staff()
            ->with('locations')
            ->get()
            ->sortBy(fn (Staff $staff): string => mb_strtolower($staff->name))
            ->values();
    }

    private function badge(Location $location): string
    {
        if ($location->isArchived()) {
            return __('Archived');
        }

        return $location->is_headquarters ? __('Headquarters') : '';
    }

    private function place(Location $location): string
    {
        $parts = array_filter([$location->city, $location->country]);

        return $parts === [] ? __('somewhere unrecorded') : implode(', ', $parts);
    }

    private function inheritNote(Location $location): string
    {
        if ($location->country === null && $location->timezone === null) {
            return __('Fill in the country and the time zone, and the people who work here inherit both.');
        }

        return __('Somebody working here inherits :country and :timezone. Both stay changeable on their own record.', [
            'country' => $location->country ?? __('no country'),
            'timezone' => $location->timezone ?? __('no time zone'),
        ]);
    }

    /** @return Collection<int, Location> */
    private function filtered(): Collection
    {
        $locations = $this->locations()->filter(fn (Location $location): bool => match ($this->scope) {
            LocationScopeEnum::Active => ! $location->isArchived(),
            LocationScopeEnum::Archived => $location->isArchived(),
            LocationScopeEnum::All => true,
        });

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $locations = $locations->filter(
                fn (Location $location): bool => str_contains(
                    mb_strtolower($location->name.' '.$location->city.' '.$location->country),
                    $needle,
                ),
            );
        }

        return $locations->sortBy(
            fn (Location $location): string => $this->sort === 'place'
                ? mb_strtolower($this->place($location))
                : mb_strtolower($location->name),
        );
    }

    /** @param  array<string, string>  $overrides */
    private function url(LocationScopeEnum $scope, array $overrides = []): string
    {
        $query = array_filter([
            'q' => $this->search,
            'sort' => $this->sort === 'name' ? '' : $this->sort,
            ...$overrides,
        ]);

        return route('settings.locations.index', array_filter([
            'scope' => $scope->segment(),
            ...$query,
        ]));
    }

    /** @return Collection<int, Location> */
    private function locations(): Collection
    {
        if ($this->locations !== null) {
            return $this->locations;
        }

        if ($this->canAddLocations()) {
            return $this->locations = $this->user->company->locations()->get();
        }

        return $this->locations = $this->user->staff?->activeLocations()->where('company_id', $this->user->company_id)->get()
            ?? new Collection;
    }
}

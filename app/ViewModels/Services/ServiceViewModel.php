<?php

declare(strict_types=1);

namespace App\ViewModels\Services;

use App\Enums\PermissionEnum;
use App\Helpers\CatalogueText;
use App\Helpers\CurrencySymbols;
use App\Models\LocationService;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Number;

class ServiceViewModel
{
    private ?SampleServiceStatistics $statistics = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $salons = null;

    public function __construct(
        private readonly User $user,
        private readonly Service $service,
    ) {}

    public function name(): string
    {
        $name = CatalogueText::resolve($this->service->name ?? [], $this->user->company->languages, app()->getLocale());

        return $name !== '' ? $name : __('Untitled service');
    }

    public function category(): string
    {
        if ($this->service->category === null) {
            return __('Without a category');
        }

        return $this->service->category->localizedName(app()->getLocale()) ?: __('Untitled category');
    }

    /** @return array<string, string|null> */
    public function breadcrumb(): array
    {
        return [
            $this->user->company->name => route('dashboard.index'),
            __('Services') => route('services.index'),
            $this->name() => null,
        ];
    }

    public function steps(): string
    {
        $count = $this->service->steps->count();

        return $count > 1 ? trans_choice(':count step|:count steps', $count) : '';
    }

    public function editUrl(): ?string
    {
        return $this->canManage() ? route('services.edit', $this->service->id) : null;
    }

    public function isArchived(): bool
    {
        return ! $this->service->is_active;
    }

    public function restoreUrl(): ?string
    {
        return $this->canManage() && $this->isArchived() ? route('serviceArchives.destroy', $this->service->id) : null;
    }

    public function description(): string
    {
        return CatalogueText::resolve($this->service->description ?? [], $this->user->company->languages, app()->getLocale());
    }

    /** @return list<array{label: string, value: string}> */
    public function facts(): array
    {
        $prices = array_map(function (array $currency): string {
            $cents = $this->service->prices[$currency['code']] ?? null;

            return $cents === null
                ? __(':symbol, no price yet', ['symbol' => $currency['symbol']])
                : "{$currency['symbol']} ".Number::format($cents / 100, maxPrecision: 2, locale: app()->getLocale());
        }, CurrencySymbols::describe($this->user->company->currencies));

        $offered = collect($this->salons())->reject(fn (array $salon): bool => $salon['isOff']);

        return [
            ['label' => __('Duration'), 'value' => __(':minutes min', ['minutes' => $this->service->duration])],
            ['label' => __('Price'), 'value' => implode(' · ', $prices)],
            ['label' => __('Offered at'), 'value' => trans_choice(':count salon|:count salons', $offered->count())],
            ['label' => __('Done by'), 'value' => trans_choice(':count person|:count people', $offered->sum(fn (array $salon): int => count($salon['people'])))],
        ];
    }

    /** @return list<array{id: int, name: string, hue: int, isHeadquarters: bool, isOff: bool, isEmpty: bool, total: string, people: list<array{staff: Staff, name: string, bookings: string}>}> */
    public function salons(): array
    {
        if ($this->salons !== null) {
            return $this->salons;
        }

        $locations = $this->user->company->locations()
            ->whereNull('archived_at')
            ->orderByDesc('is_headquarters')
            ->orderBy('name')
            ->get();
        $offers = $this->service->locationServices()
            ->where('is_active', true)
            ->with(['staff' => fn ($query) => $query->where('staff.is_active', true)])
            ->get()
            ->keyBy('location_id');

        $salons = [];

        foreach ($locations->values() as $index => $location) {
            /** @var LocationService|null $offer */
            $offer = $offers->get($location->id);
            $people = $offer === null ? collect() : $offer->staff
                ->sortByDesc(fn (Staff $staff): int => $this->statistics()->bookingsOf($staff->id))
                ->values();

            $salons[] = [
                'id' => $location->id,
                'name' => $location->name,
                'hue' => ServicesViewModel::HUES[$index % count(ServicesViewModel::HUES)],
                'isHeadquarters' => $location->is_headquarters,
                'isOff' => $offer === null,
                'isEmpty' => $offer !== null && $people->isEmpty(),
                'total' => $offer === null ? '' : trans_choice(':count booking|:count bookings', $this->statistics()->totalBookings($people->pluck('id')->all())),
                'people' => $people->map(fn (Staff $staff): array => [
                    'staff' => $staff,
                    'name' => $staff->name,
                    'bookings' => trans_choice(':count booking|:count bookings', $this->statistics()->bookingsOf($staff->id)),
                ])->all(),
            ];
        }

        return $this->salons = $salons;
    }

    /** @return list<array{label: string, value: string, trend: string, tone: string, hint: string}> */
    public function figures(): array
    {
        return $this->statistics()->figures($this->performingStaffIds());
    }

    /** @return list<array{count: int, label: string, height: int, isCurrent: bool}> */
    public function weeks(): array
    {
        return $this->statistics()->weeks($this->performingStaffIds());
    }

    public function weeksSummary(): string
    {
        $counts = array_column($this->weeks(), 'count');

        return __('Bookings per week over the last 12 weeks, from :lowest to :highest.', [
            'lowest' => min($counts),
            'highest' => max($counts),
        ]);
    }

    /** @return list<array{title: string, text: string, tone: string}> */
    public function insights(): array
    {
        $insights = $this->statistics()->insights();

        foreach ($this->salons() as $salon) {
            if ($salon['isEmpty']) {
                $insights[] = [
                    'title' => __('Cannot be booked at :salon', ['salon' => $salon['name']]),
                    'text' => __('It is offered there, but nobody is set to do it.'),
                    'tone' => 'warning',
                ];
            }
        }

        return $insights;
    }

    public function canManage(): bool
    {
        return $this->user
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    /** @return list<int> */
    private function performingStaffIds(): array
    {
        return collect($this->salons())
            ->flatMap(fn (array $salon): array => array_map(fn (array $person): int => $person['staff']->id, $salon['people']))
            ->values()
            ->all();
    }

    private function statistics(): SampleServiceStatistics
    {
        return $this->statistics ??= new SampleServiceStatistics(
            service: $this->service,
            currencySymbol: CurrencySymbols::describe($this->user->company->currencies)[0]['symbol'] ?? '',
        );
    }
}

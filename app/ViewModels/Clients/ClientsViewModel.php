<?php

declare(strict_types=1);

namespace App\ViewModels\Clients;

use App\Enums\AppointmentStatusEnum;
use App\Enums\ClientSegmentEnum;
use App\Enums\PermissionEnum;
use App\Helpers\ClientIndicators;
use App\Helpers\PhoneNumber;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ClientsViewModel
{
    public const int PER_PAGE = 50;

    private const int PHONE_SEARCH_MINIMUM_DIGITS = 3;

    /** @var Collection<int, Client>|null */
    private ?Collection $matching = null;

    /** @var LengthAwarePaginator<int, Client>|null */
    private ?LengthAwarePaginator $paginator = null;

    public function __construct(
        private readonly User $user,
        private readonly ?ClientSegmentEnum $segment = null,
        private readonly bool $notRebooked = false,
        private readonly ?Location $location = null,
        private readonly string $search = '',
        private readonly int $page = 1,
    ) {}

    /** @return array<string, string|null> */
    public function breadcrumb(): array
    {
        return [
            $this->user->company->name => route('dashboard.index'),
            __('Clients') => null,
        ];
    }

    public function search(): string
    {
        return $this->search;
    }

    public function searchUrl(): string
    {
        return $this->tabUrl($this->currentTab(), withFilters: false);
    }

    /** @return list<array{id: int, name: string, isCurrent: bool}> */
    public function locations(): array
    {
        return $this->user->company->locations()
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'isCurrent' => $location->id === $this->location?->id,
            ])
            ->all();
    }

    /** @return list<array{key: string, label: string, icon: string, count: string, url: string, isCurrent: bool}> */
    public function tabs(): array
    {
        $tabs = [
            'all' => [__('All clients'), ''],
            ClientSegmentEnum::New->value => [__('New clients'), '<circle cx="10" cy="8" r="3.5"></circle><path d="M3.5 20c0-3.6 2.9-6.5 6.5-6.5 1.4 0 2.6.4 3.6 1.1"></path><path d="M18 14v6M15 17h6"></path>'],
            ClientSegmentEnum::Regular->value => [__('Regular clients'), '<path d="M12 20s-7.5-4.6-7.5-10.2A4.2 4.2 0 0 1 12 7.2a4.2 4.2 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20z"></path><path d="M9 12.5l2 2 4-4"></path>'],
            ClientSegmentEnum::Inactive->value => [__('Inactive clients'), '<circle cx="10" cy="8" r="3.5"></circle><path d="M3.5 20c0-3.6 2.9-6.5 6.5-6.5 1.4 0 2.6.4 3.6 1.1"></path><path d="M16 15l5 5M21 15l-5 5"></path>'],
            'notRebooked' => [__('Not rebooked'), '<rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M4 10h16M8 3v4M16 3v4M10 13l4 4M14 13l-4 4"></path>'],
        ];

        $result = [];

        foreach ($tabs as $key => [$label, $icon]) {
            $result[] = [
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'count' => trans_choice('[0,*]:count', $this->inTab($key)->count()),
                'url' => $this->tabUrl($key),
                'isCurrent' => $key === $this->currentTab(),
            ];
        }

        return $result;
    }

    public function description(): string
    {
        return match ($this->currentTab()) {
            ClientSegmentEnum::New->value => __('Clients with fewer than two visits, none of them more than six months ago.'),
            ClientSegmentEnum::Regular->value => __('Clients with two visits or more, the last one in the last six months.'),
            ClientSegmentEnum::Inactive->value => __('Clients whose last visit is more than six months old.'),
            'notRebooked' => __('Clients with no appointment coming up, even if they came recently.'),
            default => __('Every client of the account, sorted by last visit.'),
        };
    }

    public function emptyText(): string
    {
        if ($this->search !== '') {
            return $this->location === null
                ? __('Nobody matches “:search” across all salons.', ['search' => $this->search])
                : __('Nobody matches “:search” at :salon.', ['search' => $this->search, 'salon' => $this->location->name]);
        }

        return match ($this->currentTab()) {
            ClientSegmentEnum::New->value => __('Nobody is a new client right now.'),
            ClientSegmentEnum::Regular->value => __('Nobody is a regular client yet.'),
            ClientSegmentEnum::Inactive->value => __('Every client came back in the last six months.'),
            'notRebooked' => __('Every client has an appointment coming up.'),
            default => __('Clients appear here after their first booking.'),
        };
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     phone: string|null,
     *     salon: string|null,
     *     lastVisit: string|null,
     *     lastVisitAgo: string|null,
     *     nextAppointment: string|null,
     *     visitCount: int,
     * }>
     */
    public function rows(): array
    {
        $clients = $this->paginator()->getCollection();

        $appointments = Appointment::query()
            ->with('location')
            ->whereKey($clients->pluck('last_visit_id')->merge($clients->pluck('next_appointment_id'))->filter()->all())
            ->get()
            ->keyBy('id');

        return $clients
            ->map(function (Client $client) use ($appointments): array {
                $lastVisit = $appointments->get($client->last_visit_id);
                $nextAppointment = $appointments->get($client->next_appointment_id);

                return [
                    'id' => $client->id,
                    'name' => $this->name($client),
                    'phone' => $client->phone === null ? null : PhoneNumber::display($client->phone),
                    'salon' => $lastVisit?->location?->name,
                    'lastVisit' => $lastVisit === null ? null : $this->localDate($lastVisit),
                    'lastVisitAgo' => $lastVisit?->starts_at->diffForHumans(),
                    'nextAppointment' => $nextAppointment === null ? null : $this->localDate($nextAppointment),
                    'visitCount' => $client->visit_count,
                ];
            })
            ->values()
            ->all();
    }

    public function footer(): string
    {
        $count = $this->paginator()->total();

        if ($this->location === null) {
            return trans_choice(':count client across all salons.|:count clients across all salons.', $count);
        }

        return trans_choice(':count client at :salon.|:count clients at :salon.', $count, ['salon' => $this->location->name]);
    }

    public function hasPage(): bool
    {
        return $this->page === 1 || $this->page <= $this->paginator()->lastPage();
    }

    /** @return LengthAwarePaginator<int, Client> */
    public function paginator(): LengthAwarePaginator
    {
        if ($this->paginator !== null) {
            return $this->paginator;
        }

        $clients = $this->inTab($this->currentTab())
            ->sortByDesc(fn (Client $client): int => $client->last_visit_at?->getTimestamp() ?? PHP_INT_MIN)
            ->values();

        return $this->paginator = new LengthAwarePaginator(
            items: $clients->forPage($this->page, self::PER_PAGE)->values(),
            total: $clients->count(),
            perPage: self::PER_PAGE,
            currentPage: $this->page,
            options: ['path' => $this->searchUrl(), 'query' => $this->filters()],
        );
    }

    private function currentTab(): string
    {
        if ($this->notRebooked) {
            return 'notRebooked';
        }

        return $this->segment->value ?? 'all';
    }

    private function tabUrl(string $tab, bool $withFilters = true): string
    {
        $query = $withFilters ? $this->filters() : [];

        return match ($tab) {
            'all' => route('clients.index', $query),
            'notRebooked' => route('clientsNotRebooked.index', $query),
            default => route('clientSegments.show', ['segment' => $tab, ...$query]),
        };
    }

    /** @return array<string, string|int> */
    private function filters(): array
    {
        return array_filter([
            'q' => $this->search,
            'location' => $this->location?->id,
        ]);
    }

    /** @return Collection<int, Client> */
    private function inTab(string $tab): Collection
    {
        return $this->matching()->filter(fn (Client $client): bool => match ($tab) {
            'all' => true,
            'notRebooked' => $client->next_appointment_id === null,
            default => ClientIndicators::segmentOf($client->visit_count, $client->last_visit_at)->value === $tab,
        });
    }

    /** @return Collection<int, Client> */
    private function matching(): Collection
    {
        if ($this->matching !== null) {
            return $this->matching;
        }

        return $this->matching = $this->visibleClients()
            ->filter(fn (Client $client): bool => $this->matchesSearch($client))
            ->values();
    }

    /**
     * Every client the user may see, with the indicators the list needs
     * counted by the database, so a few thousand clients load in one query.
     *
     * @return Collection<int, Client>
     */
    private function visibleClients(): Collection
    {
        $now = now();
        $visitStatuses = array_values(array_filter(
            AppointmentStatusEnum::cases(),
            fn (AppointmentStatusEnum $status): bool => $status->countsAsVisit(),
        ));

        $visits = fn (): Builder => Appointment::query()
            ->whereColumn('appointments.client_id', 'clients.id')
            ->whereIn('status', $visitStatuses)
            ->where('starts_at', '<=', $now);

        $upcoming = Appointment::query()
            ->whereColumn('appointments.client_id', 'clients.id')
            ->whereIn('status', $visitStatuses)
            ->where('starts_at', '>', $now);

        $query = $this->user->company->clients()
            ->select('clients.*')
            ->addSelect([
                'visit_count' => $visits()->selectRaw('count(*)'),
                'last_visit_id' => $visits()->select('id')->orderByDesc('starts_at')->orderByDesc('id')->limit(1),
                'last_visit_at' => $visits()->select('starts_at')->orderByDesc('starts_at')->limit(1),
                'next_appointment_id' => $upcoming->select('id')->orderBy('starts_at')->orderBy('id')->limit(1),
            ])
            ->withCasts([
                'visit_count' => 'integer',
                'last_visit_id' => 'integer',
                'last_visit_at' => 'datetime',
                'next_appointment_id' => 'integer',
            ]);

        if (! $this->user->permission(PermissionEnum::ClientView)->forCompany($this->user->company)->allowed()) {
            if ($this->user->staff === null) {
                return new Collection;
            }

            $query->whereHas('appointments', fn (Builder $appointments) => $appointments->where('staff_id', $this->user->staff->id));
        }

        if ($this->location !== null) {
            $query->whereHas('appointments', fn (Builder $appointments) => $appointments->where('location_id', $this->location->id));
        }

        return $query->get();
    }

    private function matchesSearch(Client $client): bool
    {
        if ($this->search === '') {
            return true;
        }

        if ($client->anonymized_at !== null) {
            return false;
        }

        $needle = Str::lower(Str::ascii($this->search));
        $haystack = Str::lower(Str::ascii("{$client->first_name} {$client->last_name} {$client->email}"));

        if (str_contains($haystack, $needle)) {
            return true;
        }

        $digits = preg_replace('/\D/', '', $this->search);

        if (mb_strlen($digits) < self::PHONE_SEARCH_MINIMUM_DIGITS) {
            return false;
        }

        // A number typed in its national form starts with a trunk zero that
        // the international form drops: 06 12 34 has to find +33 6 12 34.
        $digits = ltrim($digits, '0');

        return $digits !== '' && str_contains(preg_replace('/\D/', '', (string) $client->phone), $digits);
    }

    private function name(Client $client): string
    {
        if ($client->anonymized_at !== null) {
            return __('Erased client');
        }

        return trim("{$client->first_name} {$client->last_name}");
    }

    private function localDate(Appointment $appointment): string
    {
        $timezone = $appointment->location->timezone ?? config('app.timezone');

        return $appointment->starts_at->copy()->setTimezone($timezone)->isoFormat('ll');
    }
}

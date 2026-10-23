<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ClientSegmentEnum;
use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Support\Collection;

class ClientIndicators
{
    public const int NEW_CLIENT_VISIT_LIMIT = 2;

    public const int INACTIVE_AFTER_MONTHS = 6;

    /** @var Collection<int, Appointment> */
    private Collection $appointments;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->appointments = $client->appointments()
            ->with('services')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * A visit is an appointment that has started and was neither cancelled nor
     * a no show.
     *
     * @return Collection<int, Appointment>
     */
    public function visits(): Collection
    {
        return $this->appointments
            ->filter(fn (Appointment $appointment): bool => $appointment->status->countsAsVisit()
                && $appointment->starts_at->lessThanOrEqualTo(now()))
            ->values();
    }

    public function visitCount(): int
    {
        return $this->visits()->count();
    }

    /**
     * Get the sum of the prices recorded on the services of the visits, keyed by
     * currency. It is not what the client paid.
     *
     * @return array<string, int>
     */
    public function lifetimeSpending(): array
    {
        $spending = [];

        foreach ($this->visits() as $visit) {
            foreach ($visit->services as $service) {
                $spending[$service->currency] = ($spending[$service->currency] ?? 0) + $service->price;
            }
        }

        return $spending;
    }

    public function lastVisit(): ?Appointment
    {
        return $this->visits()->last();
    }

    /**
     * Get the average number of days between two visits, or null below two
     * visits.
     */
    public function averageVisitInterval(): ?int
    {
        $visits = $this->visits();

        if ($visits->count() < 2) {
            return null;
        }

        $days = $visits->first()->starts_at->copy()->startOfDay()
            ->diffInDays($visits->last()->starts_at->copy()->startOfDay());

        return (int) round($days / ($visits->count() - 1));
    }

    /**
     * Get the name of the service performed most often during the visits, the
     * one performed first winning a tie.
     */
    public function favouriteService(): ?string
    {
        $counts = [];

        foreach ($this->visits() as $visit) {
            foreach ($visit->services as $service) {
                $name = CatalogueText::resolve($service->name, $this->client->company->languages ?? [], app()->getLocale());
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return null;
        }

        return (string) array_search(max($counts), $counts, true);
    }

    public function nextAppointment(): ?Appointment
    {
        return $this->appointments->first(fn (Appointment $appointment): bool => $appointment->status->countsAsVisit()
            && $appointment->starts_at->isAfter(now()));
    }

    public function segment(): ClientSegmentEnum
    {
        $lastVisit = $this->lastVisit();

        if ($lastVisit !== null && $lastVisit->starts_at->lessThan(now()->subMonths(self::INACTIVE_AFTER_MONTHS))) {
            return ClientSegmentEnum::Inactive;
        }

        if ($this->visitCount() < self::NEW_CLIENT_VISIT_LIMIT) {
            return ClientSegmentEnum::New;
        }

        return ClientSegmentEnum::Regular;
    }
}

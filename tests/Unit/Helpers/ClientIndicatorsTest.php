<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers\ClientIndicatorsTest;

use App\Enums\AppointmentStatusEnum;
use App\Enums\ClientSegmentEnum;
use App\Helpers\ClientIndicators;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Client;
use Carbon\Carbon;

function appointment(Client $client, string $startsAt, string $service = "Men's Haircut", int $price = 3500, string $currency = 'CAD', AppointmentStatusEnum $status = AppointmentStatusEnum::Scheduled): Appointment
{
    $appointment = Appointment::factory()->create([
        'company_id' => $client->company_id,
        'client_id' => $client->id,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt,
        'status' => $status,
    ]);

    AppointmentService::factory()->create([
        'appointment_id' => $appointment->id,
        'name' => ['en' => $service],
        'price' => $price,
        'currency' => $currency,
    ]);

    return $appointment;
}

it('counts the visits and the days between them', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-01-01 10:00:00');
    appointment($michael, '2026-02-05 15:00:00');
    appointment($michael, '2026-03-12 09:00:00');

    $indicators = new ClientIndicators(client: $michael);

    expect($indicators->visitCount())->toBe(3);
    expect($indicators->averageVisitInterval())->toBe(35);
    expect($indicators->lastVisit()->starts_at->toDateString())->toBe('2026-03-12');
});

it('leaves cancelled and missed appointments out of the visits and the spending', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-01-01 10:00:00', price: 3500);
    appointment($michael, '2026-02-05 10:00:00', price: 9000, status: AppointmentStatusEnum::Cancelled);
    appointment($michael, '2026-03-12 10:00:00', price: 9000, status: AppointmentStatusEnum::NoShow);
    appointment($michael, '2026-03-20 10:00:00', price: 2000, status: AppointmentStatusEnum::AttendanceConfirmed);

    $indicators = new ClientIndicators(client: $michael);

    expect($indicators->visitCount())->toBe(2);
    expect($indicators->lifetimeSpending())->toBe(['CAD' => 5500]);
});

it('leaves future appointments out of the visits', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-03-01 10:00:00');
    appointment($michael, '2026-04-10 10:00:00');

    expect(new ClientIndicators(client: $michael)->visitCount())->toBe(1);
});

it('splits the spending by currency', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-01-01 10:00:00', price: 3500, currency: 'CAD');
    appointment($michael, '2026-02-01 10:00:00', price: 2500, currency: 'USD');
    appointment($michael, '2026-03-01 10:00:00', price: 1500, currency: 'CAD');

    expect(new ClientIndicators(client: $michael)->lifetimeSpending())->toBe(['CAD' => 5000, 'USD' => 2500]);
});

it('finds the favourite service, the earliest winning a tie', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-01-01 10:00:00', service: 'Beard Trim');
    appointment($michael, '2026-02-01 10:00:00', service: "Men's Haircut");
    appointment($michael, '2026-03-01 10:00:00', service: "Men's Haircut");
    appointment($michael, '2026-03-15 10:00:00', service: 'Beard Trim');

    expect(new ClientIndicators(client: $michael)->favouriteService())->toBe('Beard Trim');
});

it('finds the next appointment that is not cancelled', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-04-05 10:00:00', status: AppointmentStatusEnum::Cancelled);
    $next = appointment($michael, '2026-04-10 10:00:00');
    appointment($michael, '2026-05-10 10:00:00');

    expect(new ClientIndicators(client: $michael)->nextAppointment()->id)->toBe($next->id);
});

it('gives empty indicators to a client without visit', function () {
    $michael = Client::factory()->create();

    $indicators = new ClientIndicators(client: $michael);

    expect($indicators->visitCount())->toBe(0);
    expect($indicators->lifetimeSpending())->toBe([]);
    expect($indicators->lastVisit())->toBeNull();
    expect($indicators->averageVisitInterval())->toBeNull();
    expect($indicators->favouriteService())->toBeNull();
    expect($indicators->nextAppointment())->toBeNull();
    expect($indicators->segment())->toBe(ClientSegmentEnum::New);
});

it('calls a client with one recent visit new', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-03-01 10:00:00');

    expect(new ClientIndicators(client: $michael)->segment())->toBe(ClientSegmentEnum::New);
});

it('calls a client with recent visits regular', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-01-01 10:00:00');
    appointment($michael, '2026-02-01 10:00:00');
    appointment($michael, '2026-03-01 10:00:00');

    expect(new ClientIndicators(client: $michael)->segment())->toBe(ClientSegmentEnum::Regular);
});

it('calls a client without visit for six months inactive', function () {
    $this->travelTo('2026-10-01 12:00:00');
    $michael = Client::factory()->create();
    appointment($michael, '2026-01-01 10:00:00');
    appointment($michael, '2026-03-01 10:00:00');

    expect(new ClientIndicators(client: $michael)->segment())->toBe(ClientSegmentEnum::Inactive);
});

it('gives the segment of counted visits', function (int $visitCount, ?string $lastVisitAt, ClientSegmentEnum $segment) {
    $this->travelTo('2026-04-01 12:00:00');

    expect(ClientIndicators::segmentOf($visitCount, $lastVisitAt === null ? null : Carbon::parse($lastVisitAt)))->toBe($segment);
})->with('counted visits');

dataset('counted visits', [
    'no visit' => [0, null, ClientSegmentEnum::New],
    'one recent visit' => [1, '2026-03-30 10:00:00', ClientSegmentEnum::New],
    'three visits last week' => [3, '2026-03-25 10:00:00', ClientSegmentEnum::Regular],
    'five visits seven months ago' => [5, '2025-09-01 10:00:00', ClientSegmentEnum::Inactive],
]);

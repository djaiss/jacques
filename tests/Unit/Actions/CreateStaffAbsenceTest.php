<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\CreateStaffAbsenceTest;

use App\Actions\CreateStaffAbsence;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffAbsence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

it('records an absence at every location', function () {
    Queue::fake();

    $scranton = Location::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
    $michaelScott = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
    $michaelScott->locations()->attach([$scranton->id, $stamford->id]);
    $jan = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);

    $absence = new CreateStaffAbsence(
        author: $jan,
        staff: $michaelScott,
        startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-11-09 00:00', 'UTC'),
        reason: 'Sensei training',
        notes: 'Back for the Dundies',
    )->execute();

    expect($absence)->toBeInstanceOf(StaffAbsence::class);
    expect($absence->concernsEveryLocation())->toBeTrue();
    $this->assertDatabaseHas('staff_absences', [
        'id' => $absence->id,
        'staff_id' => $michaelScott->id,
        'location_id' => null,
        'reason' => 'Sensei training',
        'notes' => 'Back for the Dundies',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAbsenceCreated,
    );
});

it('records an absence at one location', function () {
    [$michael, $angela, $scranton] = angelaAtScranton();

    $absence = new CreateStaffAbsence(
        author: $michael,
        staff: $angela,
        startsAt: CarbonImmutable::parse('2026-11-02 09:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-11-02 12:00', 'UTC'),
        location: $scranton,
    )->execute();

    expect($absence->location_id)->toBe($scranton->id);
});

it('stores the dates in utc', function () {
    [$michael, $angela] = angelaAtScranton();

    $absence = new CreateStaffAbsence(
        author: $michael,
        staff: $angela,
        startsAt: CarbonImmutable::parse('2026-11-02 09:00', 'Europe/Paris'),
        endsAt: CarbonImmutable::parse('2026-11-02 18:00', 'Europe/Paris'),
    )->execute();

    $this->assertDatabaseHas('staff_absences', [
        'id' => $absence->id,
        'starts_at' => '2026-11-02 08:00:00',
        'ends_at' => '2026-11-02 17:00:00',
    ]);
    expect($absence->refresh()->starts_at->setTimezone('Europe/Paris')->format('H:i'))->toBe('09:00');
});

it('throws when the absence ends before it starts', function () {
    [$michael, $angela] = angelaAtScranton();

    new CreateStaffAbsence(
        author: $michael,
        staff: $angela,
        startsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member is not assigned to the location', function () {
    [$michael, $angela, $scranton] = angelaAtScranton();
    $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);

    new CreateStaffAbsence(
        author: $michael,
        staff: $angela,
        startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
        location: $stamford,
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the location belongs to another company', function () {
    [$michael, $angela] = angelaAtScranton();

    new CreateStaffAbsence(
        author: $michael,
        staff: $angela,
        startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
        location: Location::factory()->create(),
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    [$michael] = angelaAtScranton();

    new CreateStaffAbsence(
        author: $michael,
        staff: Staff::factory()->create(),
        startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
    )->execute();
})->throws(ModelNotFoundException::class);

/** @return array{0: User, 1: Staff, 2: Location} */
function angelaAtScranton(): array
{
    $scranton = Location::factory()->create();
    $angela = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Angela', 'last_name' => 'Martin']);
    $angela->locations()->attach($scranton);
    $michael = test()->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);

    return [$michael, $angela, $scranton];
}

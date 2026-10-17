<?php

declare(strict_types=1);

use App\Actions\UpdateStaffAbsence;
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

it('changes the dates the location and the reason', function () {
    Queue::fake();

    $scranton = Location::factory()->create();
    $oscar = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Oscar', 'last_name' => 'Martinez']);
    $oscar->locations()->attach($scranton);
    $absence = StaffAbsence::factory()->create(['staff_id' => $oscar->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);

    $updated = new UpdateStaffAbsence(
        author: $michael,
        absence: $absence,
        startsAt: CarbonImmutable::parse('2026-12-01 10:00', 'America/New_York'),
        endsAt: CarbonImmutable::parse('2026-12-01 15:00', 'America/New_York'),
        location: $scranton,
        reason: 'Accounting conference',
        notes: null,
    )->execute();

    expect($updated)->toBeInstanceOf(StaffAbsence::class);
    $this->assertDatabaseHas('staff_absences', [
        'id' => $absence->id,
        'location_id' => $scranton->id,
        'starts_at' => '2026-12-01 15:00:00',
        'ends_at' => '2026-12-01 20:00:00',
        'reason' => 'Accounting conference',
        'notes' => null,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAbsenceUpdated,
    );
});

it('throws when the absence ends before it starts', function () {
    $absence = StaffAbsence::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $absence->staff->company_id]), PermissionEnum::ScheduleManage);

    new UpdateStaffAbsence(
        author: $michael,
        absence: $absence,
        startsAt: CarbonImmutable::parse('2026-12-02 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-12-01 00:00', 'UTC'),
        location: null,
        reason: null,
        notes: null,
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member is not assigned to the location', function () {
    $absence = StaffAbsence::factory()->create();
    $stamford = Location::factory()->create(['company_id' => $absence->staff->company_id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $absence->staff->company_id]), PermissionEnum::ScheduleManage);

    new UpdateStaffAbsence(
        author: $michael,
        absence: $absence,
        startsAt: CarbonImmutable::parse('2026-12-01 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-12-02 00:00', 'UTC'),
        location: $stamford,
        reason: null,
        notes: null,
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the absence belongs to another company', function () {
    $absence = StaffAbsence::factory()->create();
    $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

    new UpdateStaffAbsence(
        author: $jan,
        absence: $absence,
        startsAt: CarbonImmutable::parse('2026-12-01 00:00', 'UTC'),
        endsAt: CarbonImmutable::parse('2026-12-02 00:00', 'UTC'),
        location: null,
        reason: null,
        notes: null,
    )->execute();
})->throws(ModelNotFoundException::class);

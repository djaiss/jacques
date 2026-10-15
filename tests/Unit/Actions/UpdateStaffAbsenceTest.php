<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateStaffAbsenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_dates_the_location_and_the_reason(): void
    {
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

        $this->assertInstanceOf(StaffAbsence::class, $updated);
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
    }

    #[Test]
    public function it_throws_when_the_absence_ends_before_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

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
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

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
    }

    #[Test]
    public function it_throws_when_the_absence_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

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
    }
}

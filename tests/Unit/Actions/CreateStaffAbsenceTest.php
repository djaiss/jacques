<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateStaffAbsenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_absence_at_every_location(): void
    {
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

        $this->assertInstanceOf(StaffAbsence::class, $absence);
        $this->assertTrue($absence->concernsEveryLocation());
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
    }

    #[Test]
    public function it_records_an_absence_at_one_location(): void
    {
        [$michael, $angela, $scranton] = $this->angelaAtScranton();

        $absence = new CreateStaffAbsence(
            author: $michael,
            staff: $angela,
            startsAt: CarbonImmutable::parse('2026-11-02 09:00', 'UTC'),
            endsAt: CarbonImmutable::parse('2026-11-02 12:00', 'UTC'),
            location: $scranton,
        )->execute();

        $this->assertSame($scranton->id, $absence->location_id);
    }

    #[Test]
    public function it_stores_the_dates_in_utc(): void
    {
        [$michael, $angela] = $this->angelaAtScranton();

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
        $this->assertSame('09:00', $absence->refresh()->starts_at->setTimezone('Europe/Paris')->format('H:i'));
    }

    #[Test]
    public function it_throws_when_the_absence_ends_before_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $angela] = $this->angelaAtScranton();

        new CreateStaffAbsence(
            author: $michael,
            staff: $angela,
            startsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
            endsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $angela, $scranton] = $this->angelaAtScranton();
        $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);

        new CreateStaffAbsence(
            author: $michael,
            staff: $angela,
            startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
            endsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
            location: $stamford,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        [$michael, $angela] = $this->angelaAtScranton();

        new CreateStaffAbsence(
            author: $michael,
            staff: $angela,
            startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
            endsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
            location: Location::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        [$michael] = $this->angelaAtScranton();

        new CreateStaffAbsence(
            author: $michael,
            staff: Staff::factory()->create(),
            startsAt: CarbonImmutable::parse('2026-11-02 00:00', 'UTC'),
            endsAt: CarbonImmutable::parse('2026-11-03 00:00', 'UTC'),
        )->execute();
    }

    /** @return array{0: User, 1: Staff, 2: Location} */
    private function angelaAtScranton(): array
    {
        $scranton = Location::factory()->create();
        $angela = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Angela', 'last_name' => 'Martin']);
        $angela->locations()->attach($scranton);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);

        return [$michael, $angela, $scranton];
    }
}

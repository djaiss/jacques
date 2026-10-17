<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SyncStaffWorkingHours;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncStaffWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_replaces_the_week_a_staff_member_works_at_a_location(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['name' => 'Scranton']);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'display_name' => null]);
        $jim->locations()->attach($scranton->id);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $scranton->id, 'day_of_week' => 1]);

        $result = new SyncStaffWorkingHours(
            author: $michael,
            staff: $jim,
            location: $scranton,
            week: [2 => [['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '18:00']]],
            followsOpeningHours: false,
        )->execute();

        $this->assertInstanceOf(Staff::class, $result);
        $this->assertEquals([2, 2], $jim->workingHours()->pluck('day_of_week')->all());
        $this->assertFalse((bool) $jim->locations()->first()->pivot->follows_opening_hours);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHoursUpdated
                && $job->parameters === ['name' => 'Jim Halpert', 'location' => 'Scranton'],
        );
    }

    #[Test]
    public function it_keeps_the_hours_worked_at_another_location(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create();
        $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach([$scranton->id, $stamford->id]);
        StaffWorkingHour::factory()->create(['staff_id' => $jim->id, 'location_id' => $stamford->id]);

        new SyncStaffWorkingHours(author: $michael, staff: $jim, location: $scranton, week: [], followsOpeningHours: true)->execute();

        $this->assertEquals([$stamford->id], $jim->workingHours()->pluck('location_id')->all());
        $this->assertTrue((bool) $jim->locations()->whereKey($scranton->id)->first()->pivot->follows_opening_hours);
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
        $toby = Staff::factory()->create(['company_id' => $scranton->company_id]);

        new SyncStaffWorkingHours(author: $michael, staff: $toby, location: $scranton, week: [], followsOpeningHours: true)->execute();
    }

    #[Test]
    public function it_throws_when_two_ranges_of_a_day_overlap(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach($scranton->id);

        new SyncStaffWorkingHours(
            author: $michael,
            staff: $jim,
            location: $scranton,
            week: [3 => [['start' => '09:00', 'end' => '14:00'], ['start' => '12:00', 'end' => '18:00']]],
            followsOpeningHours: false,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $nashua = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);
        $jim = Staff::factory()->create(['company_id' => $michael->company_id]);

        new SyncStaffWorkingHours(author: $michael, staff: $jim, location: $nashua, week: [], followsOpeningHours: true)->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);
        $jim = Staff::factory()->create(['company_id' => $scranton->company_id]);
        $jim->locations()->attach($scranton->id);

        new SyncStaffWorkingHours(author: $michael, staff: $jim, location: $scranton, week: [], followsOpeningHours: true)->execute();
    }
}

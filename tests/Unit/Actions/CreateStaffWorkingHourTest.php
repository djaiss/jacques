<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateStaffWorkingHour;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateStaffWorkingHourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_two_ranges_on_the_same_day_leaving_a_break(): void
    {
        Queue::fake();

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();

        $morning = new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
        $afternoon = new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '13:00', endTime: '18:00')->execute();

        $this->assertInstanceOf(StaffWorkingHour::class, $morning);
        $this->assertDatabaseHas('staff_working_hours', [
            'id' => $morning->id,
            'staff_id' => $dwight->id,
            'location_id' => $scranton->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
        $this->assertDatabaseHas('staff_working_hours', [
            'id' => $afternoon->id,
            'start_time' => '13:00:00',
            'end_time' => '18:00:00',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHourCreated,
        );
    }

    #[Test]
    public function it_accepts_a_range_that_starts_when_another_ends(): void
    {
        [$michael, $dwight, $scranton] = $this->dwightAtScranton();
        StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '12:00', endTime: '14:00')->execute();

        $this->assertSame(2, $dwight->workingHours()->count());
    }

    #[Test]
    public function it_accepts_the_same_range_on_another_day(): void
    {
        [$michael, $dwight, $scranton] = $this->dwightAtScranton();
        StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 2, startTime: '09:00', endTime: '12:00')->execute();

        $this->assertSame(2, $dwight->workingHours()->count());
    }

    #[Test]
    public function it_throws_when_the_range_overlaps_another_on_the_same_day(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();
        StaffWorkingHour::factory()->create(['staff_id' => $dwight->id, 'location_id' => $scranton->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '11:00', endTime: '14:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_range_ends_before_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '18:00', endTime: '09:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_range_starts_and_ends_at_the_same_time(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '09:00', endTime: '09:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_day_of_the_week_is_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 7, startTime: '09:00', endTime: '12:00')->execute();
    }

    #[Test]
    public function it_throws_when_a_time_is_malformed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '25:00', endTime: '26:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_is_not_assigned_to_the_location(): void
    {
        $this->expectException(InvalidArgumentException::class);

        [$michael, $dwight, $scranton] = $this->dwightAtScranton();
        $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: $stamford, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        [$michael, $dwight] = $this->dwightAtScranton();

        new CreateStaffWorkingHour(author: $michael, staff: $dwight, location: Location::factory()->create(), dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_staff_member_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        [$michael] = $this->dwightAtScranton();
        $karen = Staff::factory()->create();
        $nashua = Location::factory()->create(['company_id' => $karen->company_id]);
        $karen->locations()->attach($nashua);

        new CreateStaffWorkingHour(author: $michael, staff: $karen, location: $nashua, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
    }

    #[Test]
    public function it_throws_when_a_staff_member_sets_the_hours_of_a_colleague(): void
    {
        $this->expectException(ModelNotFoundException::class);

        [, $dwight, $scranton] = $this->dwightAtScranton();
        $jim = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::STAFF);
        Staff::factory()->create(['company_id' => $scranton->company_id, 'user_id' => $jim->id]);

        new CreateStaffWorkingHour(author: $jim, staff: $dwight, location: $scranton, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
    }

    /** @return array{0: User, 1: Staff, 2: Location} */
    private function dwightAtScranton(): array
    {
        $scranton = Location::factory()->create();
        $dwight = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $dwight->locations()->attach($scranton);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ScheduleManage, ScopeEnum::Company);

        return [$michael, $dwight, $scranton];
    }
}

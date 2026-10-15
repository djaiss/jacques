<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateStaffWorkingHour;
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

class UpdateStaffWorkingHourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_range_to_another_day_and_other_times(): void
    {
        Queue::fake();

        $morning = $this->angelaMondayMorning();
        $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

        $workingHour = new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 2, startTime: '08:30', endTime: '11:30')->execute();

        $this->assertInstanceOf(StaffWorkingHour::class, $workingHour);
        $this->assertDatabaseHas('staff_working_hours', [
            'id' => $morning->id,
            'day_of_week' => 2,
            'start_time' => '08:30:00',
            'end_time' => '11:30:00',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHourUpdated,
        );
    }

    #[Test]
    public function it_lets_a_range_overlap_its_own_previous_times(): void
    {
        $morning = $this->angelaMondayMorning();
        $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

        new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 1, startTime: '10:00', endTime: '13:00')->execute();

        $this->assertSame('10:00:00', $morning->refresh()->start_time);
    }

    #[Test]
    public function it_throws_when_the_new_times_overlap_another_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $morning = $this->angelaMondayMorning();
        StaffWorkingHour::factory()->create(['staff_id' => $morning->staff_id, 'location_id' => $morning->location_id, 'day_of_week' => 1, 'start_time' => '13:00:00', 'end_time' => '17:00:00']);
        $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

        new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 1, startTime: '09:00', endTime: '14:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_range_ends_before_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $morning = $this->angelaMondayMorning();
        $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

        new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: 1, startTime: '12:00', endTime: '09:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_day_of_the_week_is_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $morning = $this->angelaMondayMorning();
        $michael = $this->grant(User::factory()->create(['company_id' => $morning->staff->company_id]), PermissionEnum::ScheduleManage);

        new UpdateStaffWorkingHour(author: $michael, workingHour: $morning, dayOfWeek: -1, startTime: '09:00', endTime: '12:00')->execute();
    }

    #[Test]
    public function it_throws_when_the_range_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $morning = $this->angelaMondayMorning();
        $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

        new UpdateStaffWorkingHour(author: $jan, workingHour: $morning, dayOfWeek: 1, startTime: '09:00', endTime: '12:00')->execute();
    }

    private function angelaMondayMorning(): StaffWorkingHour
    {
        $scranton = Location::factory()->create();
        $angela = Staff::factory()->create(['company_id' => $scranton->company_id, 'first_name' => 'Angela', 'last_name' => 'Martin']);
        $angela->locations()->attach($scranton);

        return StaffWorkingHour::factory()->create([
            'staff_id' => $angela->id,
            'location_id' => $scranton->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
    }
}

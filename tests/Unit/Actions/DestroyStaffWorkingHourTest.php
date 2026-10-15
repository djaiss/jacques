<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyStaffWorkingHour;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyStaffWorkingHourTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_working_range(): void
    {
        Queue::fake();

        $workingHour = StaffWorkingHour::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $workingHour->staff->company_id]), PermissionEnum::ScheduleManage);

        new DestroyStaffWorkingHour(author: $michael, workingHour: $workingHour)->execute();

        $this->assertModelMissing($workingHour);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffWorkingHourDeleted,
        );
    }

    #[Test]
    public function it_throws_when_the_range_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

        new DestroyStaffWorkingHour(author: $jan, workingHour: StaffWorkingHour::factory()->create())->execute();
    }
}

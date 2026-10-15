<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyStaffAbsence;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffAbsence;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyStaffAbsenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_an_absence(): void
    {
        Queue::fake();

        $absence = StaffAbsence::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $absence->staff->company_id]), PermissionEnum::ScheduleManage);

        new DestroyStaffAbsence(author: $michael, absence: $absence)->execute();

        $this->assertModelMissing($absence);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAbsenceDeleted,
        );
    }

    #[Test]
    public function it_throws_when_the_absence_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

        new DestroyStaffAbsence(author: $jan, absence: StaffAbsence::factory()->create())->execute();
    }
}

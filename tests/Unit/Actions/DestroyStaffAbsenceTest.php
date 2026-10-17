<?php

declare(strict_types=1);

use App\Actions\DestroyStaffAbsence;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffAbsence;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('removes an absence', function () {
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
});

it('throws when the absence belongs to another company', function () {
    $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

    new DestroyStaffAbsence(author: $jan, absence: StaffAbsence::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

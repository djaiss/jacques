<?php

declare(strict_types=1);

use App\Actions\DestroyStaffWorkingHour;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffWorkingHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('removes a working range', function () {
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
});

it('throws when the range belongs to another company', function () {
    $jan = $this->grant(User::factory()->create(), PermissionEnum::ScheduleManage);

    new DestroyStaffWorkingHour(author: $jan, workingHour: StaffWorkingHour::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

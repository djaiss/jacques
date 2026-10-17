<?php

declare(strict_types=1);

use App\Actions\DeactivateStaff;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('deactivates somebody who leaves and keeps their record', function () {
    Queue::fake();

    $pam = Staff::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
    $michael = $this->grant(User::factory()->create(['company_id' => $pam->company_id]), PermissionEnum::StaffUpdate);

    $staff = new DeactivateStaff(author: $michael, staff: $pam)->execute();

    expect($staff)->toBeInstanceOf(Staff::class);
    expect($pam->refresh()->is_active)->toBeFalse();
    $this->assertModelExists($pam);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffDeactivated
            && $job->parameters === ['name' => 'Pam Beesly'],
    );
});

it('throws when the staff member belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffUpdate);

    new DeactivateStaff(author: $michael, staff: Staff::factory()->create())->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author may not change staff members', function () {
    $pam = Staff::factory()->create();
    $toby = $this->grant(User::factory()->create(['company_id' => $pam->company_id]), PermissionEnum::StaffView);

    new DeactivateStaff(author: $toby, staff: $pam)->execute();
})->throws(ModelNotFoundException::class);

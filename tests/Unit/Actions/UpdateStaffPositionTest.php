<?php

declare(strict_types=1);

use App\Actions\UpdateStaffPosition;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('moves a staff member up and shifts the others down', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $jim = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'position' => 3]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

    $staff = new UpdateStaffPosition(author: $michael, staff: $dwight, position: 1)->execute();

    expect($staff)->toBeInstanceOf(Staff::class);
    expect($dwight->refresh()->position)->toBe(1);
    expect($pam->refresh()->position)->toBe(2);
    expect($jim->refresh()->position)->toBe(3);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffPositionUpdated,
    );
});

it('moves a staff member down and shifts the others up', function () {
    $company = Company::factory()->create();
    $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $jim = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $dwight = Staff::factory()->create(['company_id' => $company->id, 'position' => 3]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

    new UpdateStaffPosition(author: $michael, staff: $pam, position: 3)->execute();

    expect($pam->refresh()->position)->toBe(3);
    expect($jim->refresh()->position)->toBe(1);
    expect($dwight->refresh()->position)->toBe(2);
});

it('leaves the staff members of another company alone', function () {
    $company = Company::factory()->create();
    $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $jim = Staff::factory()->create(['company_id' => $company->id, 'position' => 2]);
    $karen = Staff::factory()->create(['position' => 1]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

    new UpdateStaffPosition(author: $michael, staff: $jim, position: 1)->execute();

    expect($pam->refresh()->position)->toBe(2);
    expect($karen->refresh()->position)->toBe(1);
});

it('throws when the position is outside the team', function () {
    $company = Company::factory()->create();
    $pam = Staff::factory()->create(['company_id' => $company->id, 'position' => 1]);
    $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::StaffUpdate);

    new UpdateStaffPosition(author: $michael, staff: $pam, position: 2)->execute();
})->throws(InvalidArgumentException::class);

it('throws when the staff member belongs to another company', function () {
    $michael = $this->grant(User::factory()->create(), PermissionEnum::StaffUpdate);

    new UpdateStaffPosition(author: $michael, staff: Staff::factory()->create(['position' => 1]), position: 1)->execute();
})->throws(ModelNotFoundException::class);

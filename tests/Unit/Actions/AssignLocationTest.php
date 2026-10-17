<?php

declare(strict_types=1);

use App\Actions\AssignLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('assigns somebody to a location', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
    $dwight = Staff::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Dwight',
        'last_name' => 'Schrute',
        'display_name' => null,
    ]);

    $result = new AssignLocation(author: $author, staff: $dwight, location: $scranton)->execute();

    expect($result)->toBeInstanceOf(Staff::class);
    $this->assertDatabaseHas('location_staff', [
        'staff_id' => $dwight->id,
        'location_id' => $scranton->id,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::StaffAssignedToLocation
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === [
                'name' => 'Scranton',
                'staff' => 'Dwight Schrute',
            ],
    );
});

it('changes nothing when they are already assigned', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);
    $dwight->locations()->attach($scranton);

    new AssignLocation(author: $author, staff: $dwight, location: $scranton)->execute();

    expect($dwight->locations()->count())->toEqual(1);
});

it('throws when the author may not hand out roles', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $dwight = Staff::factory()->create(['company_id' => $company->id]);

    new AssignLocation(author: $michael, staff: $dwight, location: $scranton)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the staff member belongs to another company', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $stranger = Staff::factory()->create();

    new AssignLocation(author: $author, staff: $stranger, location: $scranton)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the location belongs to another company', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $nashua = Location::factory()->create();
    $dwight = Staff::factory()->create(['company_id' => $company->id]);

    new AssignLocation(author: $author, staff: $dwight, location: $nashua)->execute();
})->throws(ModelNotFoundException::class);

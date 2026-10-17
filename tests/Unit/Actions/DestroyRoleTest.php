<?php

declare(strict_types=1);

use App\Actions\DestroyRole;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('destroys a role nobody holds', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman']);
    RolePermission::factory()->create(['role_id' => $role->id]);

    new DestroyRole(author: $author, role: $role)->execute();

    $this->assertModelMissing($role);
    $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleDeleted
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === ['name' => 'Salesman'],
    );
});

it('throws when somebody still holds the role', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $dwight->roles()->attach($role->id);

    new DestroyRole(author: $author, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the role is locked', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->locked()->create(['company_id' => $company->id]);

    new DestroyRole(author: $author, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author may not administer the company', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    new DestroyRole(author: $member, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the role belongs to another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

    new DestroyRole(author: $author, role: $role)->execute();
})->throws(ModelNotFoundException::class);

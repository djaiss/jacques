<?php

declare(strict_types=1);

use App\Actions\UpdateRole;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('renames a role and replaces what it may do', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman', 'slug' => 'salesman']);
    RolePermission::factory()->create([
        'role_id' => $role->id,
        'permission' => PermissionEnum::StaffView,
        'scope' => ScopeEnum::Company,
    ]);

    $result = new UpdateRole(
        author: $author,
        role: $role,
        name: 'Assistant regional manager',
        grants: [
            ['permission' => PermissionEnum::StaffUpdate, 'scope' => ScopeEnum::Self],
        ],
        description: 'Assistant to the regional manager, really.',
    )->execute();

    expect($result)->toBeInstanceOf(Role::class);

    $role->refresh();

    expect($role->name)->toEqual('Assistant regional manager');
    expect($role->description)->toEqual('Assistant to the regional manager, really.');
    expect($role->slug)->toEqual('salesman');
    expect($role->permissions)->toHaveCount(1);
    expect($role->permissions->first()->permission)->toEqual(PermissionEnum::StaffUpdate);
    expect($role->permissions->first()->scope)->toEqual(ScopeEnum::Self);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleUpdated
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === [
                'name' => 'Assistant regional manager',
                'permissions' => 'staff.update:self',
            ],
    );
});

it('takes every permission away when none are given', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create(['role_id' => $role->id]);

    new UpdateRole(author: $author, role: $role, name: 'Salesman')->execute();

    expect($role->refresh()->permissions)->toHaveCount(0);
});

it('throws when the author may not administer the company', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    new UpdateRole(author: $member, role: $role, name: 'Salesman')->execute();
})->throws(ModelNotFoundException::class);

it('throws when the role belongs to another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);

    new UpdateRole(author: $author, role: $role, name: 'Salesman')->execute();
})->throws(ModelNotFoundException::class);

it('throws when the role is locked', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->locked()->create(['company_id' => $company->id]);

    new UpdateRole(author: $author, role: $role, name: 'Salesman')->execute();
})->throws(ModelNotFoundException::class);

it('throws when a company wide permission is granted at self scope', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);

    new UpdateRole(
        author: $author,
        role: $role,
        name: 'Salesman',
        grants: [
            ['permission' => PermissionEnum::RoleManage, 'scope' => ScopeEnum::Self],
        ],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the same permission is granted twice', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);

    new UpdateRole(
        author: $author,
        role: $role,
        name: 'Salesman',
        grants: [
            ['permission' => PermissionEnum::StaffUpdate, 'scope' => ScopeEnum::Self],
            ['permission' => PermissionEnum::StaffUpdate, 'scope' => ScopeEnum::Company],
        ],
    )->execute();
})->throws(InvalidArgumentException::class);

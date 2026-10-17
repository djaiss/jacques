<?php

declare(strict_types=1);

use App\Actions\CreateRole;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('creates a role and what it may do', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

    $role = new CreateRole(
        author: $author,
        company: $company,
        name: 'Regional manager',
        grants: [
            ['permission' => PermissionEnum::StaffView, 'scope' => ScopeEnum::Company],
            ['permission' => PermissionEnum::StaffUpdate, 'scope' => ScopeEnum::Self],
            ['permission' => PermissionEnum::ReportView, 'scope' => ScopeEnum::Location],
        ],
        description: 'Runs the Scranton branch.',
    )->execute();

    expect($role)->toBeInstanceOf(Role::class);
    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'company_id' => $company->id,
        'name' => 'Regional manager',
        'description' => 'Runs the Scranton branch.',
        'slug' => 'regional-manager',
        'is_default' => false,
        'is_editable' => true,
    ]);
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $role->id,
        'permission' => PermissionEnum::StaffView->value,
        'scope' => ScopeEnum::Company->value,
    ]);
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $role->id,
        'permission' => PermissionEnum::StaffUpdate->value,
        'scope' => ScopeEnum::Self->value,
    ]);
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $role->id,
        'permission' => PermissionEnum::ReportView->value,
        'scope' => ScopeEnum::Location->value,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleCreated
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === [
                'name' => 'Regional manager',
                'permissions' => 'staff.view:company, staff.update:self, report.view:location',
            ],
    );
});

it('makes the slug unique within the company', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

    $first = new CreateRole(author: $author, company: $company, name: 'Regional manager')->execute();
    $second = new CreateRole(author: $author, company: $company, name: 'Regional manager')->execute();

    expect($first->slug)->toEqual('regional-manager');
    expect($second->slug)->toEqual('regional-manager-2');
});

it('lets two companies use the same slug', function () {
    Queue::fake();

    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();

    $first = new CreateRole(
        author: $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage),
        company: $dunderMifflin,
        name: 'Regional manager',
    )->execute();

    $second = new CreateRole(
        author: $this->grant(User::factory()->create(['company_id' => $michaelScottPaperCompany->id]), PermissionEnum::RoleManage),
        company: $michaelScottPaperCompany,
        name: 'Regional manager',
    )->execute();

    expect($first->slug)->toEqual('regional-manager');
    expect($second->slug)->toEqual('regional-manager');
});

it('throws when the author may not administer the company', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);

    new CreateRole(author: $member, company: $company, name: 'Regional manager')->execute();
})->throws(ModelNotFoundException::class);

it('throws when the company belongs to somebody else', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);

    new CreateRole(
        author: $author,
        company: $michaelScottPaperCompany,
        name: 'Regional manager',
    )->execute();
})->throws(ModelNotFoundException::class);

it('throws when a company wide permission is granted at self scope', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

    new CreateRole(
        author: $author,
        company: $company,
        name: 'Regional manager',
        grants: [
            ['permission' => PermissionEnum::CompanyManage, 'scope' => ScopeEnum::Self],
        ],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when the same permission is granted twice', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);

    new CreateRole(
        author: $author,
        company: $company,
        name: 'Regional manager',
        grants: [
            ['permission' => PermissionEnum::StaffUpdate, 'scope' => ScopeEnum::Self],
            ['permission' => PermissionEnum::StaffUpdate, 'scope' => ScopeEnum::Company],
        ],
    )->execute();
})->throws(InvalidArgumentException::class);

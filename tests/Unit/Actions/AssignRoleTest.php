<?php

declare(strict_types=1);

use App\Actions\AssignRole;
use App\Actions\CreateDefaultRoles;
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

it('gives somebody a role', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman']);
    $dwight = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'dwight.schrute@dundermifflin.com',
    ]);

    $result = new AssignRole(author: $author, user: $dwight, role: $role)->execute();

    expect($result)->toBeInstanceOf(User::class);
    $this->assertDatabaseHas('user_roles', [
        'user_id' => $dwight->id,
        'role_id' => $role->id,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleAssigned
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === [
                'name' => 'Salesman',
                'email' => 'dwight.schrute@dundermifflin.com',
            ],
    );
});

it('changes nothing when they already hold the role', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $dwight->roles()->attach($role->id);

    new AssignRole(author: $author, user: $dwight, role: $role)->execute();

    expect($dwight->roles()->count())->toEqual(1);
});

it('gives somebody the permissions of the role', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create([
        'role_id' => $role->id,
        'permission' => PermissionEnum::CompanyManage,
        'scope' => ScopeEnum::Company,
    ]);
    $dwight = User::factory()->create(['company_id' => $company->id]);

    expect($dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeFalse();

    new AssignRole(author: $author, user: $dwight, role: $role)->execute();

    expect($dwight->fresh()->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeTrue();
});

it('lets them through straight away', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create([
        'role_id' => $role->id,
        'permission' => PermissionEnum::CompanyManage,
        'scope' => ScopeEnum::Company,
    ]);
    $dwight = User::factory()->create(['company_id' => $company->id]);

    expect($dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeFalse();

    new AssignRole(author: $author, user: $dwight, role: $role)->execute();

    expect($dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeTrue();
});

it('throws when the author may not administer the company', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);

    new AssignRole(author: $member, user: $dwight, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the user belongs to another company', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $stranger = User::factory()->create();

    new AssignRole(author: $author, user: $stranger, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the role belongs to another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
    $dwight = User::factory()->create(['company_id' => $dunderMifflin->id]);

    new AssignRole(author: $author, user: $dwight, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('replaces the role somebody held', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $staff = Role::factory()->create(['company_id' => $company->id, 'name' => 'Staff']);
    $manager = Role::factory()->create(['company_id' => $company->id, 'name' => 'Manager']);
    $jim = User::factory()->create(['company_id' => $company->id]);
    $jim->roles()->attach($staff->id);

    new AssignRole(author: $author, user: $jim, role: $manager)->execute();

    expect($jim->roles()->pluck('roles.id')->all())->toBe([$manager->id]);
});

it('throws when the last active owner would lose the owner role', function () {
    $company = Company::factory()->create();
    new CreateDefaultRoles(company: $company)->execute();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    new AssignRole(author: $michael, user: $michael, role: $company->roles()->where('slug', Role::STAFF)->firstOrFail())->execute();
})->throws(ModelNotFoundException::class);

it('lets an owner become staff when another owner remains', function () {
    Queue::fake();

    $company = Company::factory()->create();
    new CreateDefaultRoles(company: $company)->execute();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $staff = $company->roles()->where('slug', Role::STAFF)->firstOrFail();

    new AssignRole(author: $michael, user: $michael, role: $staff)->execute();

    expect($michael->roles()->pluck('roles.id')->all())->toBe([$staff->id]);
});

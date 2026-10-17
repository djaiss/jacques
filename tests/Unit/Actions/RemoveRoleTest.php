<?php

declare(strict_types=1);

use App\Actions\RemoveRole;
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

it('takes a role away from somebody', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman']);
    $dwight = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'dwight.schrute@dundermifflin.com',
    ]);
    $dwight->roles()->attach($role->id);

    $result = new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

    expect($result)->toBeInstanceOf(User::class);
    $this->assertDatabaseMissing('user_roles', [
        'user_id' => $dwight->id,
        'role_id' => $role->id,
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RoleRemoved
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === [
                'name' => 'Salesman',
                'email' => 'dwight.schrute@dundermifflin.com',
            ],
    );
});

it('changes nothing when they never held the role', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);

    new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

    expect($dwight->roles()->count())->toEqual(0);
});

it('takes the permissions of the role away too', function () {
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
    $dwight->roles()->attach($role->id);

    new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

    expect($dwight->fresh()->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeFalse();
});

it('stops letting them through straight away', function () {
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
    $dwight->roles()->attach($role->id);

    expect($dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeTrue();

    new RemoveRole(author: $author, user: $dwight, role: $role)->execute();

    expect($dwight->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeFalse();
});

it('throws when the author may not administer the company', function () {
    $company = Company::factory()->create();
    $member = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $dwight = User::factory()->create(['company_id' => $company->id]);
    $dwight->roles()->attach($role->id);

    new RemoveRole(author: $member, user: $dwight, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the user belongs to another company', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $company->id]);
    $stranger = User::factory()->create();

    new RemoveRole(author: $author, user: $stranger, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('throws when the role belongs to another company', function () {
    $dunderMifflin = Company::factory()->create();
    $michaelScottPaperCompany = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::RoleManage);
    $role = Role::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
    $dwight = User::factory()->create(['company_id' => $dunderMifflin->id]);

    new RemoveRole(author: $author, user: $dwight, role: $role)->execute();
})->throws(ModelNotFoundException::class);

it('takes the owner role away when another owner remains', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $jan = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

    new RemoveRole(author: $michael, user: $michael, role: $owner)->execute();

    expect($michael->isOwner())->toBeFalse();
    expect($jan->isOwner())->toBeTrue();
});

it('throws when the last owner would lose the role', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

    new RemoveRole(author: $michael, user: $michael, role: $owner)->execute();
})->throws(ModelNotFoundException::class);

it('does not count an owner who has been deactivated', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id, 'is_active' => false]), Role::OWNER);
    $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

    new RemoveRole(author: $michael, user: $michael, role: $owner)->execute();
})->throws(ModelNotFoundException::class);

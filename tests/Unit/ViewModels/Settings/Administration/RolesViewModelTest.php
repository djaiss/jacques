<?php

declare(strict_types=1);

use App\Actions\CreateDefaultRoles;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Settings\Administration\RolesViewModel;

it('lists the roles of the company with what each one covers', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Regional manager']);
    RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);
    RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::CompanyManage]);
    $user->roles()->attach($role->id);

    Role::factory()->create(['company_id' => $company->id, 'name' => 'Temp']);
    Role::factory()->create(['company_id' => Company::factory()->create()->id, 'name' => 'Elsewhere']);

    $rows = new RolesViewModel(user: $user, role: $role)->rows();

    expect($rows)->toHaveCount(2);
    expect($rows[0]['name'])->toEqual('Regional manager');
    expect($rows[0]['summary'])->toEqual('Administration and Team');
    expect($rows[0]['permissions'])->toEqual('2 of 17 permissions');
    expect($rows[0]['holders'])->toEqual('1 person');
    expect($rows[1]['name'])->toEqual('Temp');
    expect($rows[1]['summary'])->toEqual('Nothing yet. Tick what it is allowed to do.');
    expect($rows[1]['holders'])->toEqual('nobody');
});

it('says a role granting everything grants everything', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->create(['company_id' => $company->id, 'name' => 'Administrator']);

    foreach (PermissionEnum::cases() as $permission) {
        RolePermission::factory()->create(['role_id' => $role->id, 'permission' => $permission]);
    }

    $rows = new RolesViewModel(user: $user, role: null)->rows();

    expect($rows[0]['summary'])->toEqual('Everything, including handing out roles.');
    expect($rows[0]['badges'])->toEqual([['label' => 'Full access', 'tone' => 'accent']]);
});

it('marks a role the application looks after itself', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->locked()->create(['company_id' => $company->id]);

    $rows = new RolesViewModel(user: $user, role: null)->rows();

    expect($rows[0]['badges'])->toEqual([['label' => 'Not editable', 'tone' => 'neutral']]);
    expect(new RolesViewModel(user: $user, role: $role)->role()['isEditable'])->toBeFalse();
});

it('names the two halves of a role and says which one is being read', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);
    $user->roles()->attach($role->id);

    $tabs = new RolesViewModel(user: $user, role: $role, onPeopleTab: true)->tabs();

    expect($tabs[0]['key'])->toEqual('permissions');
    expect($tabs[0]['label'])->toEqual('Permissions · 1');
    expect($tabs[0]['current'])->toBeFalse();
    expect($tabs[1]['key'])->toEqual('people');
    expect($tabs[1]['label'])->toEqual('People · 1');
    expect($tabs[1]['current'])->toBeTrue();
    expect($tabs[1]['url'])->toEqual(route('settings.roles.show', [$role->id, 'people']));
});

it('marks the permissions the role grants and the scope of each', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create([
        'role_id' => $role->id,
        'permission' => PermissionEnum::StaffUpdate,
        'scope' => ScopeEnum::Self,
    ]);

    $groups = new RolesViewModel(user: $user, role: $role)->groups();

    $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

    expect($permissions[PermissionEnum::StaffUpdate->value]['granted'])->toBeTrue();
    expect($permissions[PermissionEnum::StaffUpdate->value]['scope'])->toEqual(ScopeEnum::Self->value);

    expect($permissions[PermissionEnum::StaffView->value]['granted'])->toBeFalse();
    expect($permissions[PermissionEnum::StaffView->value]['scope'])->toEqual(ScopeEnum::Company->value);
});

it('offers no scope for a permission that covers the whole company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $groups = new RolesViewModel(user: $user, role: $role)->groups();

    $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

    expect($permissions[PermissionEnum::CompanyManage->value]['scopes'])->toEqual([]);
    expect($permissions[PermissionEnum::StaffView->value]['scopes'])->toHaveCount(3);
    expect($permissions[PermissionEnum::ClientView->value]['scopes'])->toHaveCount(2);
});

it('shows the description of a role in place of its summary', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    Role::factory()->create(['company_id' => $company->id, 'description' => 'Answers the phone at Dunder Mifflin.']);

    $rows = new RolesViewModel(user: $user)->rows();

    expect($rows[0]['summary'])->toEqual('Answers the phone at Dunder Mifflin.');
});

it('counts what the role grants out of everything on offer', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);

    $viewModel = new RolesViewModel(user: $user, role: $role);

    expect($viewModel->grantCountLabel())->toEqual('1 of '.count(PermissionEnum::cases()).' granted');
});

it('maps every permission to whether the role grants it', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $role = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create(['role_id' => $role->id, 'permission' => PermissionEnum::StaffView]);

    $granted = new RolesViewModel(user: $user, role: $role)->grantedPermissions();

    expect($granted)->toHaveCount(count(PermissionEnum::cases()));
    expect($granted[PermissionEnum::StaffView->value])->toBeTrue();
    expect($granted[PermissionEnum::StaffUpdate->value])->toBeFalse();
});

it('words the count of permissions granted for every number it can reach', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $viewModel = new RolesViewModel(user: $user, role: $role);
    $total = count(PermissionEnum::cases());

    expect($viewModel->grantCountLabels())->toHaveCount($total + 1);
    expect($viewModel->grantCountLabels()[0])->toEqual('0 of '.$total.' granted');
    expect($viewModel->grantCountLabels()[$total])->toEqual($total.' of '.$total.' granted');

    expect($viewModel->permissionTabLabels())->toHaveCount($total + 1);
    expect($viewModel->permissionTabLabels()[0])->toEqual('Permissions · 0');
    expect($viewModel->permissionTabLabels()[$total])->toEqual('Permissions · '.$total);
});

it('words the count of permissions granted in a group for every number it can reach', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $groups = new RolesViewModel(user: $user, role: $role)->groups();

    foreach ($groups as $group) {
        $size = count($group['permissions']);

        expect($group['counts'])->toHaveCount($size + 1);
        expect($group['counts'][0])->toEqual('0 of '.$size.' granted');
        expect($group['counts'][$size])->toEqual($size.' of '.$size.' granted');
    }
});

it('warns about a role that administers the company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $quiet = Role::factory()->create(['company_id' => $company->id]);
    expect(new RolesViewModel(user: $user, role: $quiet)->warnsAboutAdministration())->toBeFalse();

    $loud = Role::factory()->create(['company_id' => $company->id]);
    RolePermission::factory()->create(['role_id' => $loud->id, 'permission' => PermissionEnum::RoleManage]);

    expect(new RolesViewModel(user: $user, role: $loud)->warnsAboutAdministration())->toBeTrue();
});

it('splits the company into who holds the role and who does not', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id, 'email' => 'michael@dundermifflin.com']);
    $colleague = User::factory()->create(['company_id' => $company->id, 'email' => 'jim@dundermifflin.com']);
    User::factory()->create(['company_id' => Company::factory()->create()->id, 'email' => 'stranger@example.com']);

    $role = Role::factory()->create(['company_id' => $company->id]);
    $user->roles()->attach($role->id);

    $viewModel = new RolesViewModel(user: $user, role: $role);

    expect($viewModel->people())->toHaveCount(1);
    expect($viewModel->people()[0]['email'])->toEqual('michael@dundermifflin.com');

    expect($viewModel->assignable())->toHaveCount(1);
    expect($viewModel->assignable()[0]['email'])->toEqual('jim@dundermifflin.com');
});

it('names the people of a role after their staff record', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id, 'email' => 'michael@dundermifflin.com']);
    $staff = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
    User::factory()->create(['company_id' => $company->id, 'email' => 'jim@dundermifflin.com']);

    $role = Role::factory()->create(['company_id' => $company->id]);
    $user->roles()->attach($role->id);

    $viewModel = new RolesViewModel(user: $user, role: $role);

    expect($viewModel->people()[0]['name'])->toEqual('Michael Scott');
    expect($staff->is($viewModel->people()[0]['staff']))->toBeTrue();
    expect($viewModel->assignable()[0]['name'])->toEqual('jim@dundermifflin.com');
    expect($viewModel->assignable()[0]['staff'])->toBeNull();
});

it('says why a role cannot be deleted', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $free = Role::factory()->create(['company_id' => $company->id]);
    $viewModel = new RolesViewModel(user: $user, role: $free);
    expect($viewModel->canBeDeleted())->toBeTrue();
    expect($viewModel->deleteHint())->toBeNull();

    $held = Role::factory()->create(['company_id' => $company->id]);
    $user->roles()->attach($held->id);
    $viewModel = new RolesViewModel(user: $user, role: $held);
    expect($viewModel->canBeDeleted())->toBeFalse();
    expect($viewModel->deleteHint())->toEqual('held by 1');

    $locked = Role::factory()->locked()->create(['company_id' => $company->id]);
    $viewModel = new RolesViewModel(user: $user, role: $locked);
    expect($viewModel->canBeDeleted())->toBeFalse();
    expect($viewModel->deleteHint())->toEqual('not editable');
});

it('holds nothing when the company has no roles left', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $viewModel = new RolesViewModel(user: $user, role: null);

    expect($viewModel->role())->toBeNull();
    expect($viewModel->rows())->toEqual([]);
    expect($viewModel->tabs())->toEqual([]);
    expect($viewModel->people())->toEqual([]);
    expect($viewModel->assignable())->toEqual([]);
    expect($viewModel->canBeDeleted())->toBeFalse();
});

it('writes out what each scope of a permission means', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $groups = new RolesViewModel(user: $user, role: $role)->groups();

    $permissions = collect($groups)->pluck('permissions')->flatten(1)->keyBy('value');

    expect($permissions[PermissionEnum::StaffView->value]['scopes'])->toEqual([
        ScopeEnum::Self->value => 'Themselves only',
        ScopeEnum::Location->value => 'Their locations',
        ScopeEnum::Company->value => 'Everybody in the company',
    ]);
});

it('keeps the last owner from being taken off the owner role', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $owner = $company->roles()->where('slug', Role::OWNER)->firstOrFail();

    $people = new RolesViewModel(user: $michael, role: $owner)->people();

    expect($people[0]['isRemovable'])->toBeFalse();

    $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $people = new RolesViewModel(user: $michael, role: $owner)->people();

    expect($people[0]['isRemovable'])->toBeTrue();
    expect($people[1]['isRemovable'])->toBeTrue();
});

it('names the role each assignable person holds today', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $jim = User::factory()->create(['company_id' => $company->id]);
    $jim->roles()->attach(Role::factory()->create(['company_id' => $company->id, 'name' => 'Salesman'])->id);
    $role = Role::factory()->create(['company_id' => $company->id]);

    $assignable = collect(new RolesViewModel(user: $user, role: $role)->assignable())->keyBy('id');

    expect($assignable[$jim->id]['currentRole'])->toBe('Salesman');
    expect($assignable[$user->id]['currentRole'])->toBeNull();
});

it('leaves the last active owner out of every other role', function () {
    $company = Company::factory()->create();
    new CreateDefaultRoles(company: $company)->execute();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    $staff = $company->roles()->where('slug', Role::STAFF)->firstOrFail();

    $assignable = new RolesViewModel(user: $michael, role: $staff)->assignable();

    expect(collect($assignable)->pluck('id')->all())->not->toContain($michael->id);
});

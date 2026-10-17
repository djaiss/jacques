<?php

declare(strict_types=1);

use App\Actions\CreateDefaultRoles;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;

it('creates the four roles a company starts with', function () {
    $company = Company::factory()->create();

    $roles = new CreateDefaultRoles(company: $company)->execute();

    expect($roles->pluck('slug')->all())->toEqual([Role::OWNER, Role::MANAGER, Role::STAFF, Role::RECEPTIONIST]);
    expect($roles->pluck('name')->all())->toEqual(['Owner', 'Manager', 'Staff', 'Receptionist']);

    foreach ($roles as $role) {
        expect($role->is_default)->toBeTrue();
        expect($role->description)->not->toBeEmpty();
        expect($role->company_id)->toEqual($company->id);
    }
});

it('locks the owner role and nothing else', function () {
    $company = Company::factory()->create();

    $roles = new CreateDefaultRoles(company: $company)->execute();

    expect($roles->mapWithKeys(fn (Role $role): array => [$role->slug => $role->is_editable])->all())->toEqual([Role::OWNER => false, Role::MANAGER => true, Role::STAFF => true, Role::RECEPTIONIST => true]);
});

it('names the roles in the language of whoever signs up', function () {
    app()->setLocale('fr_FR');
    $company = Company::factory()->create();

    $roles = new CreateDefaultRoles(company: $company)->execute();

    expect($roles->firstWhere('slug', Role::OWNER)->name)->toEqual('Propriétaire');
});

it('grants each default role exactly what it describes', function (string $slug, array $expected) {
    $company = Company::factory()->create();

    new CreateDefaultRoles(company: $company)->execute();

    $role = $company->roles()->where('slug', $slug)->firstOrFail();

    expect($role->permissions
        ->mapWithKeys(fn ($grant): array => [$grant->permission->value => $grant->scope->value])
        ->sortKeys()
        ->all())->toEqual($expected);
})->with('grantsOfEveryDefaultRole');

/** @return array<string, array{string, array<string, string>}> */
dataset('grantsOfEveryDefaultRole', function () {
    $owner = collect(PermissionEnum::cases())
        ->mapWithKeys(fn (PermissionEnum $permission): array => [$permission->value => 'company'])
        ->sortKeys()
        ->all();

    return [
        'owner' => [Role::OWNER, $owner],
        'manager' => [Role::MANAGER, [
            'appointment.manage' => 'location',
            'appointment.view' => 'location',
            'client.manage' => 'company',
            'client.view' => 'company',
            'staff.create' => 'location',
            'staff.update' => 'location',
            'staff.view' => 'location',
            'location.manage' => 'location',
            'report.view' => 'location',
            'schedule.manage' => 'location',
            'schedule.view' => 'location',
            'service.manage' => 'location',
            'service.view' => 'location',
        ]],
        'staff' => [Role::STAFF, [
            'appointment.manage' => 'self',
            'appointment.view' => 'self',
            'client.view' => 'self',
            'staff.update' => 'self',
            'staff.view' => 'self',
            'schedule.view' => 'self',
            'service.view' => 'location',
        ]],
        'receptionist' => [Role::RECEPTIONIST, [
            'appointment.manage' => 'location',
            'appointment.view' => 'location',
            'client.manage' => 'company',
            'client.view' => 'company',
            'schedule.view' => 'location',
            'service.view' => 'location',
        ]],
    ];
});

it('keeps the subscription and the roles to the owner', function () {
    $company = Company::factory()->create();

    new CreateDefaultRoles(company: $company)->execute();

    foreach ([Role::MANAGER, Role::STAFF, Role::RECEPTIONIST] as $slug) {
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), $slug);

        expect($user->permission(PermissionEnum::BillingManage)->forCompany($company)->allowed())->toBeFalse();
        expect($user->permission(PermissionEnum::RoleManage)->forCompany($company)->allowed())->toBeFalse();
        expect($user->permission(PermissionEnum::CompanyManage)->forCompany($company)->allowed())->toBeFalse();
    }
});

it('adds up staff and receptionist for somebody who holds both', function () {
    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id]);
    $jimAccount = User::factory()->create(['company_id' => $company->id]);
    $jim = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $jimAccount->id]);
    $jim->locations()->attach($scranton);

    $user = User::factory()->create(['company_id' => $company->id]);
    $pam = Staff::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
    $pam->locations()->attach($scranton);
    $this->giveDefaultRole($user, Role::STAFF);
    $this->giveDefaultRole($user, Role::RECEPTIONIST);

    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($pam)->allowed())->toBeTrue();
    expect($user->permission(PermissionEnum::StaffUpdate)->forStaff($jim)->allowed())->toBeFalse();
    expect($user->permission(PermissionEnum::ScheduleView)->forStaff($jim)->allowed())->toBeTrue();
    expect($user->grants()[PermissionEnum::AppointmentManage->value])->toEqual([ScopeEnum::Self, ScopeEnum::Location]);
});

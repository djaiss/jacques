<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\PermissionGroupEnum;
use App\Enums\ScopeEnum;

it('knows which permissions are about one staff member', function () {
    expect(PermissionEnum::StaffView->targetsStaff())->toBeTrue();
    expect(PermissionEnum::StaffUpdate->targetsStaff())->toBeTrue();
    expect(PermissionEnum::ScheduleView->targetsStaff())->toBeTrue();
    expect(PermissionEnum::ScheduleManage->targetsStaff())->toBeTrue();

    expect(PermissionEnum::StaffCreate->targetsStaff())->toBeFalse();
    expect(PermissionEnum::StaffDelete->targetsStaff())->toBeFalse();
    expect(PermissionEnum::RoleManage->targetsStaff())->toBeFalse();
    expect(PermissionEnum::LocationManage->targetsStaff())->toBeFalse();
});

it('knows which permissions are about one location', function () {
    expect(PermissionEnum::LocationManage->targetsLocation())->toBeTrue();
    expect(PermissionEnum::StaffCreate->targetsLocation())->toBeTrue();
    expect(PermissionEnum::ServiceManage->targetsLocation())->toBeTrue();
    expect(PermissionEnum::ReportView->targetsLocation())->toBeTrue();

    expect(PermissionEnum::BillingManage->targetsLocation())->toBeFalse();
    expect(PermissionEnum::StaffView->targetsLocation())->toBeFalse();
});

it('offers no choice of scope for a company wide permission', function () {
    expect(PermissionEnum::BillingManage->scopes())->toEqual([ScopeEnum::Company]);
    expect(PermissionEnum::RoleManage->scopes())->toEqual([ScopeEnum::Company]);
    expect(PermissionEnum::CompanyManage->scopes())->toEqual([ScopeEnum::Company]);
});

it('offers the locations or the company for a permission about one location', function () {
    expect(PermissionEnum::LocationManage->scopes())->toEqual([ScopeEnum::Location, ScopeEnum::Company]);
    expect(PermissionEnum::ReportView->scopes())->toEqual([ScopeEnum::Location, ScopeEnum::Company]);
});

it('offers no location scope for clients', function () {
    expect(PermissionEnum::ClientView->scopes())->toEqual([ScopeEnum::Self, ScopeEnum::Company]);
});

it('offers every scope for a permission about one staff member', function () {
    expect(PermissionEnum::StaffUpdate->scopes())->toEqual([ScopeEnum::Self, ScopeEnum::Location, ScopeEnum::Company]);
});

it('offers only the company scope for deleting a staff member', function () {
    expect(PermissionEnum::StaffDelete->scopes())->toEqual([ScopeEnum::Company]);
    expect(PermissionEnum::StaffDelete->group())->toEqual(PermissionGroupEnum::Team);
    expect(PermissionEnum::StaffDelete->label())->toEqual('Delete somebody from the team');
});

it('names every permission', function () {
    foreach (PermissionEnum::cases() as $permission) {
        expect($permission->label())->not->toBeEmpty();
    }
});

it('files every permission under a group', function () {
    expect(PermissionEnum::BillingManage->group())->toEqual(PermissionGroupEnum::Administration);
    expect(PermissionEnum::RoleManage->group())->toEqual(PermissionGroupEnum::Administration);
    expect(PermissionEnum::LocationManage->group())->toEqual(PermissionGroupEnum::Locations);
    expect(PermissionEnum::ScheduleView->group())->toEqual(PermissionGroupEnum::Locations);
    expect(PermissionEnum::StaffCreate->group())->toEqual(PermissionGroupEnum::Team);
    expect(PermissionEnum::ServiceManage->group())->toEqual(PermissionGroupEnum::Services);
    expect(PermissionEnum::ClientView->group())->toEqual(PermissionGroupEnum::Clients);
    expect(PermissionEnum::AppointmentManage->group())->toEqual(PermissionGroupEnum::Appointments);
    expect(PermissionEnum::ReportView->group())->toEqual(PermissionGroupEnum::Reports);
});

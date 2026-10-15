<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PermissionEnum;
use App\Enums\PermissionGroupEnum;
use App\Enums\ScopeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionEnumTest extends TestCase
{
    #[Test]
    public function it_knows_which_permissions_are_about_one_staff_member(): void
    {
        $this->assertTrue(PermissionEnum::StaffView->targetsStaff());
        $this->assertTrue(PermissionEnum::StaffUpdate->targetsStaff());
        $this->assertTrue(PermissionEnum::ScheduleView->targetsStaff());
        $this->assertTrue(PermissionEnum::ScheduleManage->targetsStaff());

        $this->assertFalse(PermissionEnum::StaffCreate->targetsStaff());
        $this->assertFalse(PermissionEnum::StaffDelete->targetsStaff());
        $this->assertFalse(PermissionEnum::RoleManage->targetsStaff());
        $this->assertFalse(PermissionEnum::LocationManage->targetsStaff());
    }

    #[Test]
    public function it_knows_which_permissions_are_about_one_location(): void
    {
        $this->assertTrue(PermissionEnum::LocationManage->targetsLocation());
        $this->assertTrue(PermissionEnum::StaffCreate->targetsLocation());
        $this->assertTrue(PermissionEnum::ServiceManage->targetsLocation());
        $this->assertTrue(PermissionEnum::ReportView->targetsLocation());

        $this->assertFalse(PermissionEnum::BillingManage->targetsLocation());
        $this->assertFalse(PermissionEnum::StaffView->targetsLocation());
    }

    #[Test]
    public function it_offers_no_choice_of_scope_for_a_company_wide_permission(): void
    {
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::BillingManage->scopes());
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::RoleManage->scopes());
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::CompanyManage->scopes());
    }

    #[Test]
    public function it_offers_the_locations_or_the_company_for_a_permission_about_one_location(): void
    {
        $this->assertEquals([ScopeEnum::Location, ScopeEnum::Company], PermissionEnum::LocationManage->scopes());
        $this->assertEquals([ScopeEnum::Location, ScopeEnum::Company], PermissionEnum::ReportView->scopes());
    }

    #[Test]
    public function it_offers_no_location_scope_for_clients(): void
    {
        $this->assertEquals([ScopeEnum::Self, ScopeEnum::Company], PermissionEnum::ClientView->scopes());
    }

    #[Test]
    public function it_offers_every_scope_for_a_permission_about_one_staff_member(): void
    {
        $this->assertEquals(
            [ScopeEnum::Self, ScopeEnum::Location, ScopeEnum::Company],
            PermissionEnum::StaffUpdate->scopes(),
        );
    }

    #[Test]
    public function it_offers_only_the_company_scope_for_deleting_a_staff_member(): void
    {
        $this->assertEquals([ScopeEnum::Company], PermissionEnum::StaffDelete->scopes());
        $this->assertEquals(PermissionGroupEnum::Team, PermissionEnum::StaffDelete->group());
        $this->assertEquals('Delete somebody from the team', PermissionEnum::StaffDelete->label());
    }

    #[Test]
    public function it_names_every_permission(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            $this->assertNotEmpty($permission->label());
        }
    }

    #[Test]
    public function it_files_every_permission_under_a_group(): void
    {
        $this->assertEquals(PermissionGroupEnum::Administration, PermissionEnum::BillingManage->group());
        $this->assertEquals(PermissionGroupEnum::Administration, PermissionEnum::RoleManage->group());
        $this->assertEquals(PermissionGroupEnum::Locations, PermissionEnum::LocationManage->group());
        $this->assertEquals(PermissionGroupEnum::Locations, PermissionEnum::ScheduleView->group());
        $this->assertEquals(PermissionGroupEnum::Team, PermissionEnum::StaffCreate->group());
        $this->assertEquals(PermissionGroupEnum::Services, PermissionEnum::ServiceManage->group());
        $this->assertEquals(PermissionGroupEnum::Clients, PermissionEnum::ClientView->group());
        $this->assertEquals(PermissionGroupEnum::Appointments, PermissionEnum::AppointmentManage->group());
        $this->assertEquals(PermissionGroupEnum::Reports, PermissionEnum::ReportView->group());
    }
}

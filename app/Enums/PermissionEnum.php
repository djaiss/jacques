<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum PermissionEnum: string implements Translatable
{
    // There is no permissions table: a role stores the value of the case it
    // grants, so renaming one orphans every role that already grants it.
    case BillingManage = 'billing.manage';
    case CompanyManage = 'company.manage';
    case RoleManage = 'role.manage';
    case LocationManage = 'location.manage';
    case ScheduleView = 'schedule.view';
    case ScheduleManage = 'schedule.manage';
    case EmployeeView = 'employee.view';
    case EmployeeCreate = 'employee.create';
    case EmployeeUpdate = 'employee.update';
    case ServiceView = 'service.view';
    case ServiceManage = 'service.manage';
    case ClientView = 'client.view';
    case ClientManage = 'client.manage';
    case AppointmentView = 'appointment.view';
    case AppointmentManage = 'appointment.manage';
    case ReportView = 'report.view';

    public function targetsEmployee(): bool
    {
        return match ($this) {
            self::ScheduleView,
            self::ScheduleManage,
            self::EmployeeView,
            self::EmployeeUpdate => true,
            default => false,
        };
    }

    public function targetsLocation(): bool
    {
        return match ($this) {
            self::LocationManage,
            self::EmployeeCreate,
            self::ServiceView,
            self::ServiceManage,
            self::ReportView => true,
            default => false,
        };
    }

    /** @return list<ScopeEnum> */
    public function scopes(): array
    {
        return match ($this) {
            self::BillingManage,
            self::CompanyManage,
            self::RoleManage => [ScopeEnum::Company],
            self::LocationManage,
            self::EmployeeCreate,
            self::ServiceView,
            self::ServiceManage,
            self::ReportView => [ScopeEnum::Location, ScopeEnum::Company],
            self::ClientView,
            self::ClientManage => [ScopeEnum::Self, ScopeEnum::Company],
            self::ScheduleView,
            self::ScheduleManage,
            self::EmployeeView,
            self::EmployeeUpdate,
            self::AppointmentView,
            self::AppointmentManage => [ScopeEnum::Self, ScopeEnum::Location, ScopeEnum::Company],
        };
    }

    public function group(): PermissionGroupEnum
    {
        return match ($this) {
            self::BillingManage,
            self::CompanyManage,
            self::RoleManage => PermissionGroupEnum::Administration,
            self::LocationManage,
            self::ScheduleView,
            self::ScheduleManage => PermissionGroupEnum::Locations,
            self::EmployeeView,
            self::EmployeeCreate,
            self::EmployeeUpdate => PermissionGroupEnum::Team,
            self::ServiceView,
            self::ServiceManage => PermissionGroupEnum::Services,
            self::ClientView,
            self::ClientManage => PermissionGroupEnum::Clients,
            self::AppointmentView,
            self::AppointmentManage => PermissionGroupEnum::Appointments,
            self::ReportView => PermissionGroupEnum::Reports,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BillingManage => 'Manage the Jacques subscription and its invoices',
            self::CompanyManage => 'Change the settings of the company',
            self::RoleManage => 'Hand out roles and decide who works where',
            self::LocationManage => 'Change a location and its opening hours',
            self::ScheduleView => 'See the schedule of a colleague',
            self::ScheduleManage => 'Change the schedule of a colleague',
            self::EmployeeView => 'See the profile of a colleague',
            self::EmployeeCreate => 'Add somebody to the team',
            self::EmployeeUpdate => 'Change the profile of a colleague',
            self::ServiceView => 'See the services',
            self::ServiceManage => 'Change the services',
            self::ClientView => 'See a client',
            self::ClientManage => 'Add and change clients',
            self::AppointmentView => 'See the appointments',
            self::AppointmentManage => 'Book and change appointments',
            self::ReportView => 'See the reports',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}

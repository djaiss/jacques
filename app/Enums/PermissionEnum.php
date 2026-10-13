<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    // There is no permissions table: a role stores the value of the case it
    // grants, so renaming one orphans every role that already grants it.
    case EmployeeView = 'employee.view';
    case EmployeeCreate = 'employee.create';
    case EmployeeUpdate = 'employee.update';
    case EmployeeViewPrivate = 'employee.view_private';
    case EmployeeUpdatePrivate = 'employee.update_private';
    case RoleManage = 'role.manage';
    case CompanyManage = 'company.manage';

    public function targetsEmployee(): bool
    {
        return match ($this) {
            self::EmployeeView,
            self::EmployeeUpdate,
            self::EmployeeViewPrivate,
            self::EmployeeUpdatePrivate => true,
            self::EmployeeCreate,
            self::RoleManage,
            self::CompanyManage => false,
        };
    }

    /** @return list<ScopeEnum> */
    public function scopes(): array
    {
        if (! $this->targetsEmployee()) {
            return [ScopeEnum::Company];
        }

        return [ScopeEnum::Self, ScopeEnum::Company];
    }

    public function group(): PermissionGroupEnum
    {
        return match ($this) {
            self::EmployeeView,
            self::EmployeeCreate,
            self::EmployeeUpdate => PermissionGroupEnum::People,
            self::EmployeeViewPrivate,
            self::EmployeeUpdatePrivate => PermissionGroupEnum::SensitiveData,
            self::RoleManage,
            self::CompanyManage => PermissionGroupEnum::Administration,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EmployeeView => 'See the profile of a colleague',
            self::EmployeeCreate => 'Add somebody to the company',
            self::EmployeeUpdate => 'Change the profile of a colleague',
            self::EmployeeViewPrivate => 'See the private details of a colleague',
            self::EmployeeUpdatePrivate => 'Change the private details of a colleague',
            self::RoleManage => 'Administer the company, its roles and who holds them',
            self::CompanyManage => 'Change the settings of the company',
        };
    }
}

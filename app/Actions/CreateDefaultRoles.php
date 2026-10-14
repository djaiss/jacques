<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Give a company the four roles it starts life with: Owner, Manager, Staff and
 * Receptionist. It runs when the company is created, and names the roles in the
 * language of whoever signs up, since they are the salon's to rename after.
 */
class CreateDefaultRoles
{
    /** @var Collection<int, Role> */
    private Collection $roles;

    public function __construct(
        private readonly Company $company,
    ) {}

    /** @return Collection<int, Role> */
    public function execute(): Collection
    {
        $this->roles = new Collection;

        DB::transaction(function (): void {
            foreach ($this->definitions() as $definition) {
                $this->createRole($definition);
            }
        });

        return $this->roles;
    }

    /** @return list<array{name: string, description: string, slug: string, isEditable: bool, grants: list<array{permission: PermissionEnum, scope: ScopeEnum}>}> */
    private function definitions(): array
    {
        return [
            [
                'name' => __('Owner'),
                'description' => __('Full access, including the Jacques subscription and the permissions.'),
                'slug' => Role::OWNER,
                'isEditable' => false,
                'grants' => array_map(
                    fn (PermissionEnum $permission): array => ['permission' => $permission, 'scope' => ScopeEnum::Company],
                    PermissionEnum::cases(),
                ),
            ],
            [
                'name' => __('Manager'),
                'description' => __('Runs the locations they are assigned to, without access to the Jacques subscription.'),
                'slug' => Role::MANAGER,
                'isEditable' => true,
                'grants' => [
                    ['permission' => PermissionEnum::LocationManage, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ScheduleView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ScheduleManage, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::EmployeeCreate, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ServiceView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ServiceManage, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ClientView, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::ClientManage, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::AppointmentView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::AppointmentManage, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ReportView, 'scope' => ScopeEnum::Location],
                ],
            ],
            [
                'name' => __('Staff'),
                'description' => __('Works their own schedule and appointments, and sees the clients they serve.'),
                'slug' => Role::STAFF,
                'isEditable' => true,
                'grants' => [
                    ['permission' => PermissionEnum::ScheduleView, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::ServiceView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ClientView, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::AppointmentView, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::AppointmentManage, 'scope' => ScopeEnum::Self],
                ],
            ],
            [
                'name' => __('Receptionist'),
                'description' => __('Books and changes appointments in their locations, and looks after the clients.'),
                'slug' => Role::RECEPTIONIST,
                'isEditable' => true,
                'grants' => [
                    ['permission' => PermissionEnum::ScheduleView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ServiceView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::ClientView, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::ClientManage, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::AppointmentView, 'scope' => ScopeEnum::Location],
                    ['permission' => PermissionEnum::AppointmentManage, 'scope' => ScopeEnum::Location],
                ],
            ],
        ];
    }

    /** @param  array{name: string, description: string, slug: string, isEditable: bool, grants: list<array{permission: PermissionEnum, scope: ScopeEnum}>}  $definition */
    private function createRole(array $definition): void
    {
        $role = Role::query()->create([
            'company_id' => $this->company->id,
            'name' => $definition['name'],
            'description' => $definition['description'],
            'slug' => $definition['slug'],
            'is_default' => true,
            'is_editable' => $definition['isEditable'],
        ]);

        foreach ($definition['grants'] as $grant) {
            RolePermission::query()->create([
                'role_id' => $role->id,
                'permission' => $grant['permission'],
                'scope' => $grant['scope'],
            ]);
        }

        $this->roles->push($role);
    }
}

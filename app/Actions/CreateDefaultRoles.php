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
 * Give a company the four roles it starts life with. Nobody asks for this: it
 * runs when the company is created, so there is never a company without a way
 * of handing out permissions.
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
                $this->createRole($definition['name'], $definition['slug'], $definition['grants']);
            }
        });

        return $this->roles;
    }

    /** @return list<array{name: string, slug: string, grants: list<array{permission: PermissionEnum, scope: ScopeEnum}>}> */
    private function definitions(): array
    {
        return [
            [
                'name' => 'Administrator',
                'slug' => Role::ADMINISTRATOR,
                'grants' => [
                    ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeCreate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeViewPrivate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeUpdatePrivate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::RoleManage, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::CompanyManage, 'scope' => ScopeEnum::Company],
                ],
            ],
            [
                'name' => 'People administrator',
                'slug' => Role::PEOPLE_ADMINISTRATOR,
                'grants' => [
                    ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeCreate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeViewPrivate, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeUpdatePrivate, 'scope' => ScopeEnum::Company],
                ],
            ],
            [
                'name' => 'IT administrator',
                'slug' => Role::IT_ADMINISTRATOR,
                'grants' => [
                    ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Company],
                ],
            ],
            [
                'name' => 'Member',
                'slug' => Role::MEMBER,
                'grants' => [
                    ['permission' => PermissionEnum::EmployeeView, 'scope' => ScopeEnum::Company],
                    ['permission' => PermissionEnum::EmployeeUpdate, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::EmployeeViewPrivate, 'scope' => ScopeEnum::Self],
                    ['permission' => PermissionEnum::EmployeeUpdatePrivate, 'scope' => ScopeEnum::Self],
                ],
            ],
        ];
    }

    /** @param  list<array{permission: PermissionEnum, scope: ScopeEnum}>  $grants */
    private function createRole(string $name, string $slug, array $grants): void
    {
        $role = Role::query()->create([
            'company_id' => $this->company->id,
            'name' => $name,
            'slug' => $slug,
            'is_default' => true,
            'is_editable' => true,
        ]);

        foreach ($grants as $grant) {
            RolePermission::query()->create([
                'role_id' => $role->id,
                'permission' => $grant['permission'],
                'scope' => $grant['scope'],
            ]);
        }

        $this->roles->push($role);
    }
}

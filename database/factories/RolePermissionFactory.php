<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RolePermission>
 */
class RolePermissionFactory extends Factory
{
    protected $model = RolePermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'permission' => PermissionEnum::StaffView,
            'scope' => ScopeEnum::Company,
        ];
    }
}

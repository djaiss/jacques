<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use Carbon\Carbon;
use Database\Factories\RolePermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RolePermission
 *
 * One thing a role is allowed to do, and the scope saying over which staff members
 * it is allowed to do it. A role grants a permission once, at one scope.
 *
 * @property int $id
 * @property int $role_id
 * @property PermissionEnum $permission
 * @property ScopeEnum $scope
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class RolePermission extends Model
{
    /** @use HasFactory<RolePermissionFactory> */
    use HasFactory;

    protected $table = 'role_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'permission',
        'scope',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permission' => PermissionEnum::class,
            'scope' => ScopeEnum::class,
        ];
    }

    /**
     * Get the role the permission is granted to.
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}

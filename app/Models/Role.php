<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Role
 *
 * A named bundle of permissions inside one company. Users are given permissions
 * through roles and in no other way.
 *
 * Every company starts with four of them: Owner, Manager, Staff and
 * Receptionist. Owner grants everything and is locked, so it can be handed out
 * but never changed or deleted.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property bool $is_default
 * @property bool $is_editable
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * The slug of the role the first user of a company is given, and the one
     * the application looks up to tell who owns the company.
     */
    public const string OWNER = 'owner';

    public const string MANAGER = 'manager';

    public const string STAFF = 'staff';

    public const string RECEPTIONIST = 'receptionist';

    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'slug',
        'is_default',
        'is_editable',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_editable' => 'boolean',
        ];
    }

    /**
     * Get the company the role belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get what the role is allowed to do, and over whom.
     *
     * @return HasMany<RolePermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Get the users who hold the role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }
}

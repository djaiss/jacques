<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\TimeFormatEnum;
use App\Permissions\PendingPermissionCheck;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $employee_id
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password_hash
 * @property Carbon|null $password_changed_at
 * @property string|null $sso_provider
 * @property bool $is_active
 * @property string|null $locale
 * @property TimeFormatEnum $time_format
 * @property Carbon|null $last_signed_in_at
 * @property string|null $last_sign_in_ip
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    /**
     * Every permission the roles of the user grant, and the scopes each one is
     * granted at, kept for the rest of the request once it has been read.
     *
     * @var array<string, list<ScopeEnum>>|null
     */
    private ?array $grants = null;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'email',
        'email_verified_at',
        'password_hash',
        'password_changed_at',
        'sso_provider',
        'is_active',
        'locale',
        'time_format',
        'last_signed_in_at',
        'last_sign_in_ip',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'time_format' => TimeFormatEnum::class,
            'last_signed_in_at' => 'datetime',
            'is_active' => 'boolean',
            'password_hash' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * Get the company the user belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee the account gives access to, when the account belongs to
     * somebody who works for the company.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the roles the user holds, which is the only way they are given any
     * permission at all.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /**
     * Get the locations the user is assigned to, which is what a permission
     * granted at location scope reaches.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'user_locations')->withTimestamps();
    }

    /**
     * Get whether the user holds the Owner role, which grants everything and
     * which a company never runs out of.
     */
    public function isOwner(): bool
    {
        return $this->roles()->where('slug', Role::OWNER)->exists();
    }

    /**
     * Get the magic links issued for the user.
     *
     * @return HasMany<MagicLink, $this>
     */
    public function magicLinks(): HasMany
    {
        return $this->hasMany(MagicLink::class);
    }

    /**
     * Get the actions the user performed.
     *
     * @return HasMany<Log, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get the emails the application sent to the user.
     *
     * @return HasMany<EmailSent, $this>
     */
    public function emailsSent(): HasMany
    {
        return $this->hasMany(EmailSent::class);
    }

    /**
     * Start a permission check, to be finished by naming what it is about:
     *
     *     $user->permission(PermissionEnum::EmployeeUpdate)
     *         ->forEmployee($employee)
     *         ->authorize();
     */
    public function permission(PermissionEnum $permission): PendingPermissionCheck
    {
        return new PendingPermissionCheck($this, $permission);
    }

    /**
     * Get every permission the roles of the user grant, and the scopes each one
     * is granted at, with the grants of several roles added together.
     *
     * It is read once and kept for the rest of the request, since a screen asks
     * the same question about a great many rows. Anything that changes which
     * roles the user holds has to say so through forgetGrants(), or the checks
     * that follow answer from what was true beforehand.
     *
     * @return array<string, list<ScopeEnum>>
     */
    public function grants(): array
    {
        return $this->grants ??= RolePermission::query()
            ->whereIn('role_id', $this->roles()->select('roles.id'))
            ->get()
            ->groupBy(fn (RolePermission $grant): string => $grant->permission->value)
            ->map(fn ($grants): array => $grants->pluck('scope')->unique()->values()->all())
            ->all();
    }

    /**
     * Throw away the grants read earlier, so the next check reads them again.
     * Giving somebody a role or taking one away has to do this, otherwise a
     * role that was just removed still lets them through for the rest of the
     * request.
     */
    public function forgetGrants(): void
    {
        $this->grants = null;
    }

    /**
     * Reload the user from the database. The grants are not an attribute, so
     * they survive this unless they are dropped here too, and somebody who
     * refreshes a user expects to be looking at what the database says.
     */
    public function refresh()
    {
        $this->forgetGrants();

        return parent::refresh();
    }

    /**
     * Get the password of the user. The column is named password_hash, so the
     * guard needs to be told where to look.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /**
     * Get whether the user signs in through an SSO provider rather than with a
     * password.
     */
    public function usesSingleSignOn(): bool
    {
        return $this->sso_provider !== null;
    }

    /**
     * Get whether the user confirmed their email address.
     */
    public function hasConfirmedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Get whether the user finished enrolling in two factor authentication, and
     * therefore has to answer a challenge before they are signed in.
     */
    public function usesTwoFactorAuthentication(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }
}

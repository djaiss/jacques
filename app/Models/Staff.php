<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Staff
 *
 * Somebody who works for a company. A staff member exists on their own, with
 * or without an account to sign in with, and an account gives access to one
 * staff member at most.
 *
 * Somebody who leaves is deactivated. An inactive or unbookable staff member,
 * like a paused location assignment, keeps what was written about them but is
 * never offered for booking.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $display_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $job_title
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property bool $is_active
 * @property bool $is_bookable
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    /**
     * The size, in CSS pixels, the avatar is displayed at. It is written to disk
     * twice, at this size and at twice it, so a dense screen has a sharp
     * version to pick from. 96 covers the 74 pixel avatar on the profile
     * screen, and downsizes cleanly to the 26 pixel one in the sidebar.
     */
    public const int AVATAR_SIZE = 96;

    protected $table = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'user_id',
        'first_name',
        'last_name',
        'display_name',
        'email',
        'phone',
        'job_title',
        'bio',
        'avatar_path',
        'is_active',
        'is_bookable',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_bookable' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the company the staff member works for.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the account of the staff member, when they have one.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the locations the staff member is assigned to, paused assignments
     * included. Only an active assignment is what a permission granted at
     * location scope reaches.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_staff')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Get the locations where the assignment of the staff member is in effect.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function activeLocations(): BelongsToMany
    {
        return $this->locations()->wherePivot('is_active', true);
    }

    /**
     * Get the services the staff member performs, each as offered at one location.
     *
     * @return BelongsToMany<LocationService, $this>
     */
    public function locationServices(): BelongsToMany
    {
        return $this->belongsToMany(LocationService::class, 'staff_location_services')->withTimestamps();
    }

    /**
     * Get the weekly time ranges the staff member works, at every location.
     *
     * @return HasMany<StaffWorkingHour, $this>
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(StaffWorkingHour::class);
    }

    /**
     * Get the dated periods the staff member does not work.
     *
     * @return HasMany<StaffAbsence, $this>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(StaffAbsence::class);
    }

    /**
     * Get the name the staff member should be called by, which is the name they
     * go by when they gave one, and their legal name otherwise.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->display_name ?? $this->full_name,
        );
    }

    /**
     * Get the legal name of the staff member, which is their first name alone
     * when no last name was given.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->last_name === null
                ? $this->first_name
                : "{$this->first_name} {$this->last_name}",
        );
    }

    /**
     * Every pixel size actually written to disk, which is what the route that
     * serves the avatar accepts.
     *
     * @return list<int>
     */
    public static function avatarPixelSizes(): array
    {
        return [self::AVATAR_SIZE, self::AVATAR_SIZE * 2];
    }

    public function hasAvatar(): bool
    {
        return $this->avatar_path !== null;
    }

    /**
     * The path of one version of the avatar. avatar_path holds the base the two
     * versions are named after, without an extension, so the files beside it
     * are avatars/{staff}/{uuid}_96.webp and avatars/{staff}/{uuid}_192.webp.
     */
    public function avatarVariantPath(int $pixels): string
    {
        return "{$this->avatar_path}_{$pixels}.webp";
    }
}

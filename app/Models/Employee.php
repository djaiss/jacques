<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Employee
 *
 * Somebody who works for a company. An employee exists on their own, before
 * they arrive and after they leave, and does not necessarily have an account to
 * sign in with.
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $employee_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $display_name
 * @property string|null $avatar_path
 * @property string|null $work_email
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * The size, in CSS pixels, the avatar is displayed at. It is written to disk
     * twice, at this size and at twice it, so a dense screen has a sharp
     * version to pick from. 96 covers the 74 pixel avatar on the profile
     * screen, and downsizes cleanly to the 26 pixel one in the sidebar.
     */
    public const int AVATAR_SIZE = 96;

    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_number',
        'first_name',
        'last_name',
        'display_name',
        'avatar_path',
        'work_email',
    ];

    /**
     * Get the company the employee works for.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the account of the employee, when they have one.
     *
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the name the employee should be called by, which is the name they go
     * by when they gave one, and their legal name otherwise.
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
     * Get the legal name of the employee, as it appears on their contract.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->first_name.' '.$this->last_name,
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
     * are avatars/{employee}/{uuid}_96.webp and avatars/{employee}/{uuid}_192.webp.
     */
    public function avatarVariantPath(int $pixels): string
    {
        return $this->avatar_path.'_'.$pixels.'.webp';
    }
}

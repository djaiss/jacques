<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OnboardingStepEnum;
use Carbon\Carbon;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Company
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $legal_name
 * @property string|null $logo_path
 * @property string|null $website_url
 * @property string|null $currency
 * @property string|null $billing_email
 * @property Carbon|null $trial_ends_at
 * @property bool $is_welcome_screen_pending
 * @property bool $is_onboarding_pending
 * @property OnboardingStepEnum $onboarding_step
 * @property list<string> $languages
 * @property array<string, mixed>|null $settings
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'logo_path',
        'website_url',
        'currency',
        'billing_email',
        'trial_ends_at',
        'is_welcome_screen_pending',
        'is_onboarding_pending',
        'onboarding_step',
        'languages',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'is_welcome_screen_pending' => 'boolean',
            'is_onboarding_pending' => 'boolean',
            'onboarding_step' => OnboardingStepEnum::class,
            'languages' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * Get the users who belong to the company.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the roles that exist inside the company.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the locations of the company.
     *
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the service categories of the company, in the order of their position.
     *
     * @return HasMany<ServiceCategory, $this>
     */
    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class)->orderBy('position');
    }

    /**
     * Get the services of the company.
     *
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get whether the company is still within its trial period.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }
}

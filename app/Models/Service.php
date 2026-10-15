<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\CatalogueText;
use Carbon\Carbon;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Service
 *
 * Something the salon sells, defined once for the whole company. A service is a
 * sequence of steps, each with an active part and a processing part during
 * which the team member is free for somebody else, and its duration is the sum
 * of them.
 *
 * A service is available at a location only once it has been added there,
 * through LocationService, which may also set another price for that location.
 * A deactivated service is offered nowhere, but its row stays.
 *
 * Its name and description are written once per language of the salon, keyed
 * by language code. Texts in a language the salon stopped using are kept.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $service_category_id
 * @property array<string, string> $name
 * @property array<string, string|null>|null $description
 * @property int $price
 * @property bool $is_active
 * @property int $position
 * @property-read int $duration
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $table = 'services';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'service_category_id',
        'name',
        'description',
        'price',
        'is_active',
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
            'name' => 'array',
            'description' => 'array',
            'price' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the company the service belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category the service is listed in, when it has one.
     *
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * Get the steps of the service, in the order they are performed.
     *
     * @return HasMany<ServiceStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ServiceStep::class)->orderBy('position');
    }

    /**
     * Get the service as it is offered at each location.
     *
     * @return HasMany<LocationService, $this>
     */
    public function locationServices(): HasMany
    {
        return $this->hasMany(LocationService::class);
    }

    /**
     * Get the locations that offer the service.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_services')
            ->withPivot(['price', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the duration of the service in minutes: the active and processing
     * parts of every step, added together.
     *
     * @return Attribute<int, never>
     */
    protected function duration(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->steps->sum(
            fn (ServiceStep $step): int => $step->active_duration + $step->processing_duration,
        ));
    }

    /**
     * Get the name in the language of the interface, or in the first language of
     * the salon that has one.
     */
    public function localizedName(string $locale): string
    {
        return CatalogueText::resolve($this->name, $this->company->languages, $locale);
    }

    /**
     * Get the languages of the salon the name is not written in yet.
     *
     * @return list<string>
     */
    public function missingLanguages(): array
    {
        return CatalogueText::missingLanguages($this->name, $this->company->languages);
    }
}

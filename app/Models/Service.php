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
 * sequence of steps, each one either keeping the team member busy or leaving
 * them free for somebody else, and its duration is the sum of them. At least
 * one step keeps the team member busy.
 *
 * A service is available at a location only once it has been added there,
 * through LocationService, which may also set another price for that location.
 * A deactivated service is offered nowhere, but its row stays.
 *
 * Its name and description are written once per language of the salon, keyed
 * by language code. A language may still be missing, and texts in a language
 * the salon stopped using are kept.
 *
 * Its price is written once per currency of the salon, in the smallest unit of
 * that currency, keyed by currency code. A currency may still be missing.
 * Nothing converts one into another, and prices in a currency the salon stopped
 * using are kept. A service can be booked in a currency only once it has a
 * price in it and a name in the first language of the salon.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $service_category_id
 * @property array<string, string> $name
 * @property array<string, string|null>|null $description
 * @property array<string, int> $prices
 * @property bool $is_active
 * @property int $position
 * @property-read int $duration
 * @property-read int $busy_duration
 * @property-read int $free_duration
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
        'prices',
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
            'prices' => 'array',
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
            ->withPivot(['prices', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the duration of the service in minutes: every step added together.
     *
     * @return Attribute<int, never>
     */
    protected function duration(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->steps->sum('duration'));
    }

    /**
     * Get the minutes during which the team member works on the client.
     *
     * @return Attribute<int, never>
     */
    protected function busyDuration(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->steps->where('requires_staff', true)->sum('duration'));
    }

    /**
     * Get the minutes during which the team member is free for somebody else.
     *
     * @return Attribute<int, never>
     */
    protected function freeDuration(): Attribute
    {
        return Attribute::get(fn (): int => $this->duration - $this->busy_duration);
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
     * Get the texts a client may read, each keyed by language code: the name,
     * the description once it is written in any language, and the step names
     * once the service has more than one step.
     *
     * @return list<array<string, string|null>>
     */
    public function translatableTexts(): array
    {
        $texts = [$this->name];

        if (array_filter($this->description ?? []) !== []) {
            $texts[] = $this->description;
        }

        if ($this->steps->count() > 1) {
            foreach ($this->steps as $step) {
                $texts[] = $step->name ?? [];
            }
        }

        return $texts;
    }

    /**
     * Get the languages of the salon in which one of its texts is not written
     * yet.
     *
     * @return list<string>
     */
    public function missingLanguages(): array
    {
        $missing = [];

        foreach ($this->translatableTexts() as $texts) {
            $missing = [...$missing, ...CatalogueText::missingLanguages($texts, $this->company->languages)];
        }

        return array_values(array_intersect($this->company->languages, $missing));
    }

    /**
     * Get the currencies of the salon the service has no price in yet, in the
     * order of the salon.
     *
     * @return list<string>
     */
    public function missingCurrencies(): array
    {
        return array_values(array_filter(
            $this->company->currencies,
            fn (string $currency): bool => ! array_key_exists($currency, $this->prices),
        ));
    }

    /**
     * Whether a client can book the service in a currency: it needs a price in
     * that currency and a name in the first language of the salon.
     */
    public function isBookableIn(string $currency): bool
    {
        if (! array_key_exists($currency, $this->prices)) {
            return false;
        }

        return ($this->name[$this->company->languages[0]] ?? '') !== '';
    }
}

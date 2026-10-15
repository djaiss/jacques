<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\CatalogueText;
use Carbon\Carbon;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ServiceCategory
 *
 * A group of services in the catalogue of a company, such as cuts or colours.
 * Categories are listed in the order of their position, and deleting one keeps
 * its services, which are then listed without a category.
 *
 * Its name and description are written once per language of the salon, keyed
 * by language code. Texts in a language the salon stopped using are kept.
 *
 * @property int $id
 * @property int $company_id
 * @property array<string, string> $name
 * @property array<string, string|null>|null $description
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ServiceCategory extends Model
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use HasFactory;

    protected $table = 'service_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
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
        ];
    }

    /**
     * Get the company the category belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the services listed in the category, in the order of their position.
     *
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('position');
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

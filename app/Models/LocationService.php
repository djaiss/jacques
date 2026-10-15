<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\LocationServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class LocationService
 *
 * A service, as one location offers it. A service is available at a location
 * only once this row exists, and the location may stop offering it for a while
 * without losing the row.
 *
 * The prices, keyed by currency code, replace the prices of the service at
 * this location in the currencies they name. In any other currency the
 * location follows the price of the service, including when that price
 * changes later. The duration cannot be changed here: it is
 * the sum of the steps of the service, the same everywhere.
 *
 * @property int $id
 * @property int $location_id
 * @property int $service_id
 * @property array<string, int>|null $prices
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class LocationService extends Model
{
    /** @use HasFactory<LocationServiceFactory> */
    use HasFactory;

    protected $table = 'location_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',
        'service_id',
        'prices',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prices' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the location that offers the service.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the service the location offers.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the staff members who perform the service at the location.
     *
     * @return BelongsToMany<Staff, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_location_services')->withTimestamps();
    }

    /**
     * Get the price at the location in a currency: its own when it has one, the
     * price of the service otherwise, and null when neither has one.
     */
    public function resolvedPrice(string $currency): ?int
    {
        return $this->prices[$currency] ?? $this->service->prices[$currency] ?? null;
    }
}

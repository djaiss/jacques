<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\LocationOpeningHourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LocationOpeningHour
 *
 * One weekly time range during which a location is open. A day can hold
 * several ranges, and the gap between two of them is a closure, such as
 * lunch. A day with no range is a day the location is closed. Ranges of the
 * same location and day never overlap, and none crosses midnight.
 *
 * Times are written as H:i:s and read in the time zone of the location, since
 * a salon opens at the same time on the clock all year round.
 *
 * @property int $id
 * @property int $location_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class LocationOpeningHour extends Model
{
    /** @use HasFactory<LocationOpeningHourFactory> */
    use HasFactory;

    protected $table = 'location_opening_hours';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    /**
     * Get the location open during the range.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}

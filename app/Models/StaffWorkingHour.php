<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\StaffWorkingHourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StaffWorkingHour
 *
 * One weekly time range a staff member works at a location. A day can hold
 * several ranges, and the gap between two of them is a break. Ranges of the
 * same staff member, location and day never overlap, and none crosses
 * midnight.
 *
 * Times are written as H:i:s and read in the time zone of the location, since
 * a working day starts at the same time on the clock all year round.
 *
 * @property int $id
 * @property int $staff_id
 * @property int $location_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class StaffWorkingHour extends Model
{
    /** @use HasFactory<StaffWorkingHourFactory> */
    use HasFactory;

    protected $table = 'staff_working_hours';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
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
     * Get the staff member who works during the range.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the location the staff member works at during the range.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\LocationStaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LocationStaff
 *
 * One staff member assigned to one location, and the day they were assigned.
 *
 * A permission granted at location scope reaches the locations where the
 * assignment of a person is active, and nothing else. A paused assignment
 * keeps its row, so resuming it gives back what was set up there. People are
 * assigned and removed through the relation on the two models it joins, which
 * is what AssignLocation and RemoveLocation use. This is here for what the
 * relation cannot say: when the row itself was written.
 *
 * @property int $id
 * @property int $location_id
 * @property int $staff_id
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class LocationStaff extends Model
{
    /** @use HasFactory<LocationStaffFactory> */
    use HasFactory;

    protected $table = 'location_staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',
        'staff_id',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the location the staff member is assigned to.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the staff member assigned to the location.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}

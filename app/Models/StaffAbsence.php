<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\StaffAbsenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StaffAbsence
 *
 * A dated period during which a staff member does not work. An absence with a
 * location concerns that location only; an absence without one concerns every
 * location of the staff member. Its dates are stored in UTC and shown in the
 * time zone of the salon. Two absences may overlap.
 *
 * @property int $id
 * @property int $staff_id
 * @property int|null $location_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $reason
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class StaffAbsence extends Model
{
    /** @use HasFactory<StaffAbsenceFactory> */
    use HasFactory;

    protected $table = 'staff_absences';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'location_id',
        'starts_at',
        'ends_at',
        'reason',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Get the staff member who is absent.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the location the absence concerns, when it does not concern them all.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function concernsEveryLocation(): bool
    {
        return $this->location_id === null;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\StaffLocationServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StaffLocationService
 *
 * One staff member performing one service at one location. A staff member can
 * perform a service only where their assignment is active and where the
 * service is offered, and taking them off the location removes what they
 * performed there.
 *
 * Rows are written through the relation on Staff, which is what
 * AssignStaffLocationService and RemoveStaffLocationService use. This is here
 * for what the relation cannot say: when the row itself was written.
 *
 * @property int $id
 * @property int $staff_id
 * @property int $location_service_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class StaffLocationService extends Model
{
    /** @use HasFactory<StaffLocationServiceFactory> */
    use HasFactory;

    protected $table = 'staff_location_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'location_service_id',
    ];

    /**
     * Get the staff member who performs the service.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the service, as offered at the location, that the staff member performs.
     *
     * @return BelongsTo<LocationService, $this>
     */
    public function locationService(): BelongsTo
    {
        return $this->belongsTo(LocationService::class);
    }
}

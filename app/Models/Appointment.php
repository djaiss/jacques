<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatusEnum;
use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Appointment
 *
 * A time booked for one client with one staff member in one location. Its
 * times are stored in UTC and shown in the time zone of the salon.
 *
 * It has four statuses. A past appointment that is neither cancelled nor a no
 * show counts as a visit without anybody clicking anything. The location and
 * the staff member become null when they are deleted, so the history of the
 * client survives them.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $location_id
 * @property int $client_id
 * @property int|null $staff_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property AppointmentStatusEnum $status
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $table = 'appointments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'location_id',
        'client_id',
        'staff_id',
        'starts_at',
        'ends_at',
        'status',
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
            'status' => AppointmentStatusEnum::class,
        ];
    }

    /**
     * Get the company the appointment is booked with.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the location the appointment takes place in.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the client who receives the services.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the staff member who performs the services.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the services performed in the appointment, in their order.
     *
     * @return HasMany<AppointmentService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(AppointmentService::class)->orderBy('position');
    }
}

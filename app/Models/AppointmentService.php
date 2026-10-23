<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\CatalogueText;
use Carbon\Carbon;
use Database\Factories\AppointmentServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AppointmentService
 *
 * A service performed in an appointment. It copies the name, the price and the
 * duration the service had at booking, so a later change of the catalogue
 * never rewrites the past. The link to the catalogue becomes null when the
 * service is deleted, and the copy stays.
 *
 * @property int $id
 * @property int $appointment_id
 * @property int|null $service_id
 * @property array<string, string|null> $name
 * @property int $price
 * @property string $currency
 * @property int $duration
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class AppointmentService extends Model
{
    /** @use HasFactory<AppointmentServiceFactory> */
    use HasFactory;

    protected $table = 'appointment_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'appointment_id',
        'service_id',
        'name',
        'price',
        'currency',
        'duration',
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
            'price' => 'integer',
            'duration' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * Get the appointment the service is performed in.
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the service of the catalogue it was booked from.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the technical notes recorded for the service.
     *
     * @return HasMany<ClientTechnicalNote, $this>
     */
    public function technicalNotes(): HasMany
    {
        return $this->hasMany(ClientTechnicalNote::class);
    }

    /**
     * Get the name in the language of the interface, or in the first language of
     * the salon that has one.
     */
    public function localizedName(string $locale): string
    {
        return CatalogueText::resolve($this->name, $this->appointment->company->languages, $locale);
    }
}

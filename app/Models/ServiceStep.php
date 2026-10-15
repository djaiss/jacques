<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ServiceStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ServiceStep
 *
 * One part of a service. During a step that requires staff, the team member
 * works on the client; during a step that does not, the team member is free for
 * somebody else, while a colour sets for instance. That second kind is the
 * processing time of the product.
 *
 * Its name is written once per language of the salon, keyed by language code,
 * and stays empty on a service made of a single step.
 *
 * Steps are written with their service, never one by one, so a service always
 * has at least one. The position is the rank of the step in the service.
 *
 * @property int $id
 * @property int $service_id
 * @property int $position
 * @property array<string, string>|null $name
 * @property int $duration
 * @property bool $requires_staff
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class ServiceStep extends Model
{
    /** @use HasFactory<ServiceStepFactory> */
    use HasFactory;

    protected $table = 'service_steps';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'position',
        'name',
        'duration',
        'requires_staff',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'name' => 'array',
            'duration' => 'integer',
            'requires_staff' => 'boolean',
        ];
    }

    /**
     * Get the service the step is part of.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

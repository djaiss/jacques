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
 * One part of a service. The active duration is the time the team member works
 * on the client; the processing duration follows it, and during it the team
 * member is free for somebody else, while a colour sets for instance.
 *
 * Steps are written with their service, never one by one, so a service always
 * has at least one. The position is the rank of the step in the service.
 *
 * @property int $id
 * @property int $service_id
 * @property int $position
 * @property int $active_duration
 * @property int $processing_duration
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
        'active_duration',
        'processing_duration',
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
            'active_duration' => 'integer',
            'processing_duration' => 'integer',
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

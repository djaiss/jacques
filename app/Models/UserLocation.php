<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserLocation
 *
 * One person assigned to one location, and the day they were assigned.
 *
 * A permission granted at location scope reaches the locations a person is
 * assigned to, and nothing else. People are assigned and removed through the
 * relation on the two models it joins, which is what AssignLocation and
 * RemoveLocation use. This is here for what the relation cannot say: when the
 * row itself was written.
 *
 * @property int $id
 * @property int $user_id
 * @property int $location_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class UserLocation extends Model
{
    /** @use HasFactory<UserLocationFactory> */
    use HasFactory;

    protected $table = 'user_locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'location_id',
    ];

    /**
     * Get the person assigned to the location.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location they are assigned to.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TeamMemberServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TeamMemberService
 *
 * One team member performing one service at one location. A team member can
 * perform a service only where they are assigned and where the service is
 * offered, and taking them off the location removes what they performed there.
 *
 * Rows are written through the relation on LocationService, which is what
 * AssignTeamMemberService and RemoveTeamMemberService use. This is here for
 * what the relation cannot say: when the row itself was written.
 *
 * @property int $id
 * @property int $user_id
 * @property int $location_service_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class TeamMemberService extends Model
{
    /** @use HasFactory<TeamMemberServiceFactory> */
    use HasFactory;

    protected $table = 'team_member_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'location_service_id',
    ];

    /**
     * Get the team member who performs the service.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service, as offered at the location, that the team member performs.
     *
     * @return BelongsTo<LocationService, $this>
     */
    public function locationService(): BelongsTo
    {
        return $this->belongsTo(LocationService::class);
    }
}

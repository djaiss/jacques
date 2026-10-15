<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\StaffInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StaffInvitation
 *
 * A request, prepared by somebody of the company, for a staff member to create
 * an account, and the role that account will hold. It goes to the email
 * address of the staff member, so a staff member without one has no
 * invitation, and has one invitation at most.
 *
 * Preparing an invitation sends nothing. Sending it and accepting it are the
 * work of a later feature, which reads the invitations prepared here.
 *
 * @property int $id
 * @property int $staff_id
 * @property int $role_id
 * @property int|null $author_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
class StaffInvitation extends Model
{
    /** @use HasFactory<StaffInvitationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'role_id',
        'author_id',
    ];

    /**
     * Get the staff member invited to create an account.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the role the account will hold once it is created.
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user who prepared the invitation, while their account exists.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

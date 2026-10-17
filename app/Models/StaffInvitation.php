<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffInvitationStatusEnum;
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
 * Preparing an invitation sends nothing. Each send draws a new link, whose
 * token reaches the database only as a hash, and which stops working when it
 * expires, when the invitation is sent again, revoked or accepted. Its state
 * is read from these dates rather than stored, so the two cannot disagree.
 *
 * @property int $id
 * @property int $staff_id
 * @property int $role_id
 * @property int|null $author_id
 * @property string|null $token_hash
 * @property Carbon|null $sent_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
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
        'token_hash',
        'sent_at',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

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

    /**
     * Get where the invitation stands. Acceptance and revocation are final,
     * an invitation never sent is prepared, and a sent one is pending until
     * its link expires.
     */
    public function status(): StaffInvitationStatusEnum
    {
        if ($this->accepted_at !== null) {
            return StaffInvitationStatusEnum::Accepted;
        }

        if ($this->revoked_at !== null) {
            return StaffInvitationStatusEnum::Revoked;
        }

        if ($this->sent_at === null) {
            return StaffInvitationStatusEnum::Prepared;
        }

        if ($this->expires_at === null || $this->expires_at->isPast()) {
            return StaffInvitationStatusEnum::Expired;
        }

        return StaffInvitationStatusEnum::Pending;
    }

    /**
     * Whether the link of the latest send can still be accepted.
     */
    public function isUsable(): bool
    {
        return $this->status() === StaffInvitationStatusEnum::Pending;
    }
}

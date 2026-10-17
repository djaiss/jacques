<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\StaffInvitationStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\StaffInvitationMail;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Email a staff member the link that accepts their invitation. Sending again
 * draws a new link and the previous one stops working. Two clicks a moment
 * apart send one email, and a staff member is emailed a few times a day at
 * most, so the button cannot be used to flood somebody's inbox.
 */
class SendStaffInvitation
{
    public const int MAX_SENDS_PER_DAY = 3;

    private const int SECONDS_BETWEEN_SENDS = 60;

    private StaffInvitation $invitation;

    private ?string $token = null;

    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): StaffInvitation
    {
        $this->authorize();

        DB::transaction(function (): void {
            $this->lock();
            $this->validate();

            if ($this->wasJustSent()) {
                return;
            }

            $this->throttle();
            $this->send();
        });

        if ($this->token !== null) {
            $this->log();
        }

        return $this->invitation;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->staff->company)
            ->authorize();
    }

    private function lock(): void
    {
        $this->staff->refresh();

        $invitation = StaffInvitation::query()
            ->where('staff_id', $this->staff->id)
            ->lockForUpdate()
            ->first();

        if ($invitation === null) {
            throw new ModelNotFoundException('Invitation not found');
        }

        $this->invitation = $invitation;
    }

    private function validate(): void
    {
        if ($this->invitation->role->company_id !== $this->staff->company_id) {
            throw new ModelNotFoundException('Role not found');
        }

        if ($this->staff->company->is_onboarding_pending) {
            throw new InvalidArgumentException('Invitations are sent once the onboarding is finished');
        }

        if (in_array($this->invitation->status(), [StaffInvitationStatusEnum::Accepted, StaffInvitationStatusEnum::Revoked], true)) {
            throw new InvalidArgumentException('An accepted or revoked invitation cannot be sent');
        }

        if ($this->staff->email === null || $this->staff->user_id !== null || ! $this->staff->is_active) {
            throw new InvalidArgumentException('The staff member cannot be invited');
        }
    }

    private function wasJustSent(): bool
    {
        return $this->invitation->sent_at !== null
            && $this->invitation->sent_at->diffInSeconds(now()) < self::SECONDS_BETWEEN_SENDS;
    }

    private function throttle(): void
    {
        $key = "staff-invitation:{$this->staff->id}";

        if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS_PER_DAY)) {
            throw ValidationException::withMessages([
                'invitation' => trans_choice('[0,*]This invitation was already sent :count times today. Try again tomorrow.', self::MAX_SENDS_PER_DAY),
            ]);
        }

        RateLimiter::hit($key, 86400);
    }

    private function send(): void
    {
        $this->token = Str::random(64);
        $days = (int) config('jacques.staff_invitation_duration_days');

        $this->invitation->token_hash = hash('sha256', $this->token);
        $this->invitation->sent_at = now();
        $this->invitation->expires_at = now()->addDays($days);
        $this->invitation->save();

        SendEmail::dispatch(
            mailable: new StaffInvitationMail(
                url: route('invitations.show', ['token' => $this->token]),
                inviter: $this->author->staff->name ?? $this->author->email,
                salon: $this->staff->company->name,
                role: $this->invitation->role->name,
                firstName: $this->staff->first_name,
                days: $days,
            ),
            company: $this->staff->company,
            emailType: EmailTypeEnum::StaffInvitation,
            emailAddress: $this->staff->email,
        )->onQueue('high')->afterCommit();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffInvitationSent,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}

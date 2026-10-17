<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Accept the invitation a link stands for, and give the staff member their
 * account. Somebody new gets an account from the password they chose, while
 * somebody signed in uses theirs, provided it is the address the invitation
 * went to and the account belongs to the same salon. Linking the account,
 * giving the role and spending the link happen together or not at all, so
 * two clicks on the same link give one account.
 */
class AcceptStaffInvitation
{
    private StaffInvitation $invitation;

    public function __construct(
        private readonly string $token,
        private ?User $user = null,
        private readonly ?string $password = null,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->lock();
            $this->validate();
            $this->createUser();
            $this->validateUser();
            $this->accept();
        });

        $this->log();

        return $this->user;
    }

    private function lock(): void
    {
        $invitation = StaffInvitation::query()
            ->with(['staff.company', 'role'])
            ->where('token_hash', hash('sha256', $this->token))
            ->lockForUpdate()
            ->first();

        if ($invitation === null) {
            throw new ModelNotFoundException('Invitation not found');
        }

        $this->invitation = $invitation;
    }

    private function validate(): void
    {
        $staff = $this->invitation->staff;

        if (! $this->invitation->isUsable()) {
            throw new InvalidArgumentException('The invitation can no longer be accepted');
        }

        if ($staff->is_active === false || $staff->email === null || $staff->user_id !== null) {
            throw new InvalidArgumentException('The staff member can no longer be invited');
        }

        if ($this->invitation->role->company_id !== $staff->company_id) {
            throw new ModelNotFoundException('Role not found');
        }
    }

    private function createUser(): void
    {
        if ($this->user !== null) {
            return;
        }

        if ($this->password === null) {
            throw new InvalidArgumentException('A new account needs a password');
        }

        if (User::query()->where('email', $this->invitation->staff->email)->exists()) {
            throw new InvalidArgumentException('An account already uses this email address');
        }

        $this->user = new CreateUser(
            company: $this->invitation->staff->company,
            email: $this->invitation->staff->email,
            password: $this->password,
        )->execute();

        $this->user->email_verified_at = now();
        $this->user->save();
    }

    private function validateUser(): void
    {
        if (mb_strtolower($this->user->email) !== mb_strtolower((string) $this->invitation->staff->email)) {
            throw new InvalidArgumentException('The invitation was sent to another email address');
        }

        if ($this->user->company_id !== $this->invitation->staff->company_id) {
            throw new InvalidArgumentException('The account belongs to another salon');
        }
    }

    private function accept(): void
    {
        $staff = $this->invitation->staff;
        $staff->user_id = $this->user->id;
        $staff->save();

        $this->user->roles()->syncWithoutDetaching([$this->invitation->role_id]);
        $this->user->forgetGrants();

        $this->invitation->accepted_at = now();
        $this->invitation->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->invitation->staff->company,
            user: $this->user,
            action: UserActionEnum::StaffInvitationAccepted,
            parameters: ['name' => $this->invitation->staff->company->name],
        )->onQueue('low');
    }
}

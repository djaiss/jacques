<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\StaffInvitationStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Withdraw an invitation already sent, so its link stops working. The
 * invitation stays, revoked, until somebody prepares a new one.
 */
class RevokeStaffInvitation
{
    private StaffInvitation $invitation;

    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): StaffInvitation
    {
        $this->authorize();
        $this->validate();
        $this->revoke();
        $this->log();

        return $this->invitation;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->staff->company)
            ->authorize();
    }

    private function validate(): void
    {
        $invitation = $this->staff->invitation;

        if ($invitation === null) {
            throw new ModelNotFoundException('Invitation not found');
        }

        if ($invitation->status() !== StaffInvitationStatusEnum::Pending) {
            throw new InvalidArgumentException('Only a pending invitation can be revoked');
        }

        $this->invitation = $invitation;
    }

    private function revoke(): void
    {
        $this->invitation->revoked_at = now();
        $this->invitation->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffInvitationRevoked,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}

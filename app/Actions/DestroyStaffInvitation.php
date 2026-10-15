<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;

/**
 * Withdraw the invitation prepared for a staff member. Withdrawing when none
 * was prepared changes nothing and records nothing.
 */
class DestroyStaffInvitation
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): void
    {
        $this->authorize();

        if ($this->staff->invitation()->delete() === 0) {
            return;
        }

        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->staff->company)
            ->authorize();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffInvitationDeleted,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}

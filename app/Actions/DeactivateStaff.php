<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;

/**
 * Deactivate somebody who leaves the company. Their record and everything
 * attached to it stay, so the history keeps its author and activating them
 * again brings everything back. An inactive staff member is never offered for
 * booking.
 */
class DeactivateStaff
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->deactivate();
        $this->log();

        return $this->staff;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::StaffUpdate)
            ->forStaff($this->staff)
            ->authorize();
    }

    private function deactivate(): void
    {
        $this->staff->is_active = false;
        $this->staff->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffDeactivated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}

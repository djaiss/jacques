<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;

/**
 * Bring back a staff member who was deactivated, with their locations, their
 * services and their hours as they were left.
 */
class ActivateStaff
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->activate();
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

    private function activate(): void
    {
        $this->staff->is_active = true;
        $this->staff->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffActivated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}

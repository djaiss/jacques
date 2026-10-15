<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffAbsence;
use App\Models\User;

/**
 * Remove an absence of a staff member.
 */
class DestroyStaffAbsence
{
    public function __construct(
        private readonly User $author,
        private readonly StaffAbsence $absence,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->log();
        $this->destroy();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ScheduleManage)
            ->forStaff($this->absence->staff)
            ->authorize();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->absence->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffAbsenceDeleted,
            parameters: ['name' => $this->absence->staff->name],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->absence->delete();
    }
}

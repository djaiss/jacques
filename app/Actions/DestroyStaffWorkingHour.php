<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StaffWorkingHour;
use App\Models\User;

/**
 * Remove a weekly working range of a staff member.
 */
class DestroyStaffWorkingHour
{
    public function __construct(
        private readonly User $author,
        private readonly StaffWorkingHour $workingHour,
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
            ->forStaff($this->workingHour->staff)
            ->authorize();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->workingHour->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffWorkingHourDeleted,
            parameters: [
                'name' => $this->workingHour->staff->name,
                'location' => $this->workingHour->location->name,
            ],
        )->onQueue('low');
    }

    private function destroy(): void
    {
        $this->workingHour->delete();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Assign somebody to a location, which is what a permission granted at
 * location scope reaches. Assigning somebody who is already there changes
 * nothing.
 */
class AssignLocation
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly Location $location,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->validate();
        $this->assign();
        $this->log();

        return $this->staff;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->location->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->staff->company_id !== $this->location->company_id) {
            throw new ModelNotFoundException('Staff not found');
        }
    }

    private function assign(): void
    {
        $this->staff->locations()->syncWithoutDetaching([$this->location->id]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::StaffAssignedToLocation,
            parameters: [
                'name' => $this->location->name,
                'staff' => $this->staff->name,
            ],
        )->onQueue('low');
    }
}

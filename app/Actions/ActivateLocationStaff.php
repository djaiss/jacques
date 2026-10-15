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
use InvalidArgumentException;

/**
 * Resume the assignment of a staff member to a location. What their roles grant
 * at location scope reaches it again, and they can be offered services there.
 */
class ActivateLocationStaff
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
        $this->activate();
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

        if (! $this->staff->locations()->whereKey($this->location->id)->exists()) {
            throw new InvalidArgumentException('The staff member is not assigned to the location');
        }
    }

    private function activate(): void
    {
        $this->staff->locations()->updateExistingPivot($this->location->id, ['is_active' => true]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationStaffActivated,
            parameters: [
                'name' => $this->location->name,
                'staff' => $this->staff->name,
            ],
        )->onQueue('low');
    }
}

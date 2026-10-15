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
 * Pause the assignment of a staff member to a location without removing it.
 * What their roles grant at location scope stops reaching it and no new
 * service can be given to them there, but their services, hours and absences
 * at the location stay, ready for when the assignment resumes.
 */
class DeactivateLocationStaff
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
        $this->deactivate();
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

    private function deactivate(): void
    {
        $this->staff->locations()->updateExistingPivot($this->location->id, ['is_active' => false]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationStaffDeactivated,
            parameters: [
                'name' => $this->location->name,
                'staff' => $this->staff->name,
            ],
        )->onQueue('low');
    }
}

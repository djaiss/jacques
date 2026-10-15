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
use Illuminate\Support\Facades\DB;

/**
 * Take somebody off a location. What their roles grant at location scope stops
 * reaching it, and the services, working hours and absences they had there
 * are forgotten. An absence that concerns every location stays. Taking
 * somebody off a location they were never assigned to changes nothing.
 */
class RemoveLocation
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
        $this->remove();
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

    private function remove(): void
    {
        DB::transaction(function (): void {
            $this->staff->locations()->detach($this->location->id);
            $this->staff->locationServices()->detach($this->location->locationServices()->pluck('id'));
            $this->staff->workingHours()->where('location_id', $this->location->id)->delete();
            $this->staff->absences()->where('location_id', $this->location->id)->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::StaffRemovedFromLocation,
            parameters: [
                'name' => $this->location->name,
                'staff' => $this->staff->name,
            ],
        )->onQueue('low');
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take somebody off a location. What their roles grant at location scope stops
 * reaching it, and taking somebody off a location they were never assigned to
 * changes nothing.
 */
class RemoveLocation
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
        private readonly Location $location,
    ) {}

    public function execute(): User
    {
        $this->authorize();
        $this->validate();
        $this->remove();
        $this->log();

        return $this->user;
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
        if ($this->user->company_id !== $this->location->company_id) {
            throw new ModelNotFoundException('User not found');
        }
    }

    private function remove(): void
    {
        $this->user->locations()->detach($this->location->id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::UserRemovedFromLocation,
            parameters: [
                'name' => $this->location->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}

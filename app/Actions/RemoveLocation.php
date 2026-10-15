<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take somebody off a location. What their roles grant at location scope stops
 * reaching it, the services they performed there are forgotten, and taking
 * somebody off a location they were never assigned to changes nothing.
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
        DB::transaction(function (): void {
            $this->user->locations()->detach($this->location->id);
            $this->user->locationServices()->detach($this->location->locationServices()->pluck('id'));
        });
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

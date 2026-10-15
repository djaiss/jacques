<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop a team member performing a service at a location. Removing somebody who
 * never performed it there changes nothing.
 */
class RemoveTeamMemberService
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
        private readonly LocationService $locationService,
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
            ->permission(PermissionEnum::ServiceManage)
            ->forLocation($this->locationService->location)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->user->company_id !== $this->locationService->location->company_id) {
            throw new ModelNotFoundException('User not found');
        }
    }

    private function remove(): void
    {
        $this->user->locationServices()->detach($this->locationService->id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->locationService->location->company,
            user: $this->author,
            action: UserActionEnum::TeamMemberServiceRemoved,
            parameters: [
                'name' => $this->locationService->service->localizedName($this->locationService->service->company->languages[0]),
                'location' => $this->locationService->location->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}

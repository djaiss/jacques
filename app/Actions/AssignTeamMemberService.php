<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LocationService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Let a team member perform a service at a location. They have to be assigned
 * to the location, and the location has to offer the service. Assigning somebody
 * who already performs it there changes nothing.
 */
class AssignTeamMemberService
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
        $this->assign();
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

        $assigned = $this->user->locations()
            ->whereKey($this->locationService->location_id)
            ->exists();

        if (! $assigned) {
            throw new InvalidArgumentException('A team member can only perform a service at a location they are assigned to');
        }

        if (! $this->locationService->is_active || ! $this->locationService->service->is_active) {
            throw new InvalidArgumentException('The location does not offer the service called '.$this->locationService->service->name);
        }
    }

    private function assign(): void
    {
        $this->user->locationServices()->syncWithoutDetaching([$this->locationService->id]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->locationService->location->company,
            user: $this->author,
            action: UserActionEnum::TeamMemberServiceAssigned,
            parameters: [
                'name' => $this->locationService->service->name,
                'location' => $this->locationService->location->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}

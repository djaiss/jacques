<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;

/**
 * Delete a location of a company.
 */
class DestroyLocation
{
    private string $name;

    public function __construct(
        private readonly User $author,
        private readonly Location $location,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->destroy();
        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->location->company)
            ->authorize();
    }

    private function destroy(): void
    {
        $this->name = $this->location->name;

        $this->location->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationDeleted,
            parameters: ['name' => $this->name],
        )->onQueue('low');
    }
}

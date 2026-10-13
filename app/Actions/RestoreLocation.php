<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;

/**
 * Reopen a location the company had closed. It comes back as an ordinary location
 * rather than as the headquarters, since being closed is what took that away.
 */
class RestoreLocation
{
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
    ) {}

    public function execute(): Location
    {
        $this->authorize();
        $this->restore();
        $this->log();

        return $this->location;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->location->company)
            ->authorize();
    }

    private function restore(): void
    {
        $this->location->archived_at = null;
        $this->location->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationRestored,
            parameters: ['name' => $this->location->name],
        )->onQueue('low');
    }
}

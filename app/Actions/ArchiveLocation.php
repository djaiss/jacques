<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;

/**
 * Close a location of a company. The row stays, so what was written down about
 * the location is still there to read, and the list simply stops offering it.
 */
class ArchiveLocation
{
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
    ) {}

    public function execute(): Location
    {
        $this->authorize();
        $this->archive();
        $this->log();

        return $this->location;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::LocationManage)
            ->forLocation($this->location)
            ->authorize();
    }

    private function archive(): void
    {
        $this->location->archived_at = now();
        $this->location->is_headquarters = false;
        $this->location->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationArchived,
            parameters: ['name' => $this->location->name],
        )->onQueue('low');
    }
}

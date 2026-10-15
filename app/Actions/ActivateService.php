<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\User;

/**
 * Offer a deactivated service again, at the locations that offered it before
 * and with the team members who performed it there.
 */
class ActivateService
{
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->activate();
        $this->log();

        return $this->service;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->service->company)
            ->authorize();
    }

    private function activate(): void
    {
        $this->service->is_active = true;
        $this->service->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServiceActivated,
            parameters: ['name' => $this->service->name],
        )->onQueue('low');
    }
}

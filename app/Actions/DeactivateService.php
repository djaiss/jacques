<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\User;

/**
 * Stop offering a service anywhere. The row, its steps and where it was offered
 * all stay, so what was recorded about the service is still there to read and
 * activating it again brings it back as it was.
 */
class DeactivateService
{
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->deactivate();
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

    private function deactivate(): void
    {
        $this->service->is_active = false;
        $this->service->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServiceDeactivated,
            parameters: ['name' => $this->service->localizedName($this->service->company->languages[0])],
        )->onQueue('low');
    }
}

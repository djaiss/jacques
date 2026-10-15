<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Delete a service from the catalogue, with its steps, the locations that offer
 * it and the team members who perform it there. The services after it in its
 * category move up one place.
 */
class DestroyService
{
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
    ) {}

    public function execute(): void
    {
        $this->authorize();

        DB::transaction(function (): void {
            $this->destroy();
        });

        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->service->company)
            ->authorize();
    }

    private function destroy(): void
    {
        Service::query()
            ->where('company_id', $this->service->company_id)
            ->where('service_category_id', $this->service->service_category_id)
            ->where('position', '>', $this->service->position)
            ->decrement('position');

        $this->service->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServiceDeleted,
            parameters: ['name' => $this->service->localizedName($this->service->company->languages[0])],
        )->onQueue('low');
    }
}

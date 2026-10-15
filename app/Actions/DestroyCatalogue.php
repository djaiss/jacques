<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Empty the catalogue of a company: every category and every service goes,
 * with their steps and the locations that offered them.
 */
class DestroyCatalogue
{
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
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
            ->forCompany($this->company)
            ->authorize();
    }

    private function destroy(): void
    {
        Service::query()->where('company_id', $this->company->id)->delete();
        ServiceCategory::query()->where('company_id', $this->company->id)->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::CatalogueDestroyed,
        )->onQueue('low');
    }
}

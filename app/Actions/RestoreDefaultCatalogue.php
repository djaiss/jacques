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
use InvalidArgumentException;

/**
 * Bring the example catalogue back into a company whose catalogue is empty, as
 * it was written at sign up.
 */
class RestoreDefaultCatalogue
{
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            new CopyDefaultCatalogue(company: $this->company)->execute();
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

    private function validate(): void
    {
        $hasCatalogue = ServiceCategory::query()->where('company_id', $this->company->id)->exists()
            || Service::query()->where('company_id', $this->company->id)->exists();

        if ($hasCatalogue) {
            throw new InvalidArgumentException('The example catalogue only comes back into an empty catalogue');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::CatalogueRestored,
        )->onQueue('low');
    }
}

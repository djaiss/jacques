<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Delete a service category. Its services stay in the catalogue: they join the
 * services without a category, after the ones already there, and the
 * categories after it move up one place.
 */
class DestroyServiceCategory
{
    public function __construct(
        private readonly User $author,
        private readonly ServiceCategory $category,
    ) {}

    public function execute(): void
    {
        $this->authorize();

        DB::transaction(function (): void {
            $this->releaseServices();
            $this->destroy();
        });

        $this->log();
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->category->company)
            ->authorize();
    }

    private function releaseServices(): void
    {
        $lastPosition = (int) Service::query()
            ->where('company_id', $this->category->company_id)
            ->whereNull('service_category_id')
            ->max('position');

        foreach ($this->category->services as $service) {
            $lastPosition++;

            $service->service_category_id = null;
            $service->position = $lastPosition;
            $service->save();
        }
    }

    private function destroy(): void
    {
        ServiceCategory::query()
            ->where('company_id', $this->category->company_id)
            ->where('position', '>', $this->category->position)
            ->decrement('position');

        $this->category->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->category->company,
            user: $this->author,
            action: UserActionEnum::ServiceCategoryDeleted,
            parameters: ['name' => $this->category->localizedName($this->category->company->languages[0])],
        )->onQueue('low');
    }
}

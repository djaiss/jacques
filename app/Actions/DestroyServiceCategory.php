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
 * Delete a service category, and the categories after it move up one place.
 * Its services either stay in the catalogue, joining the services without a
 * category after the ones already there, or go with it when asked to.
 */
class DestroyServiceCategory
{
    public function __construct(
        private readonly User $author,
        private readonly ServiceCategory $category,
        private readonly bool $withServices = false,
    ) {}

    public function execute(): void
    {
        $this->authorize();

        DB::transaction(function (): void {
            if ($this->withServices) {
                $this->destroyServices();
            }

            if (! $this->withServices) {
                $this->releaseServices();
            }

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

    private function destroyServices(): void
    {
        $this->category->services()->delete();
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

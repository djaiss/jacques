<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put back what was just removed from a catalogue, from the snapshot taken
 * before the removal: categories and services return to the place they had,
 * the ones after them moving down to make room. They come back as new rows.
 */
class RestoreCatalogueSnapshot
{
    /**
     * @param  array{categories: list<array<string, mixed>>, services: list<array<string, mixed>>}  $snapshot
     */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private readonly array $snapshot,
    ) {}

    public function execute(): void
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            foreach ($this->snapshot['categories'] as $category) {
                $this->restoreCategory($category);
            }

            foreach ($this->snapshot['services'] as $service) {
                $this->restoreService($service, $service['service_category_id']);
            }
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
        $categoryIds = array_values(array_filter(array_column($this->snapshot['services'], 'service_category_id')));

        $known = ServiceCategory::query()
            ->where('company_id', $this->company->id)
            ->whereIn('id', $categoryIds)
            ->count();

        if ($known !== count(array_unique($categoryIds))) {
            throw new ModelNotFoundException('Service category not found');
        }
    }

    /**
     * @param  array<string, mixed>  $category
     */
    private function restoreCategory(array $category): void
    {
        ServiceCategory::query()
            ->where('company_id', $this->company->id)
            ->where('position', '>=', $category['position'])
            ->increment('position');

        $restored = ServiceCategory::query()->create([
            'company_id' => $this->company->id,
            'name' => $category['name'],
            'description' => $category['description'],
            'position' => $category['position'],
        ]);

        foreach ($category['services'] as $service) {
            $this->restoreService($service, $restored->id);
        }
    }

    /**
     * @param  array<string, mixed>  $service
     */
    private function restoreService(array $service, ?int $categoryId): void
    {
        Service::query()
            ->where('company_id', $this->company->id)
            ->where('service_category_id', $categoryId)
            ->where('position', '>=', $service['position'])
            ->increment('position');

        $restored = Service::query()->create([
            'company_id' => $this->company->id,
            'service_category_id' => $categoryId,
            'name' => $service['name'],
            'description' => $service['description'],
            'prices' => $service['prices'],
            'is_active' => $service['is_active'],
            'position' => $service['position'],
        ]);

        foreach ($service['steps'] as $index => $step) {
            ServiceStep::query()->create([
                'service_id' => $restored->id,
                'position' => $index + 1,
                'name' => $step['name'],
                'duration' => $step['duration'],
                'requires_staff' => $step['requires_staff'],
            ]);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::CatalogueRemovalRestored,
        )->onQueue('low');
    }
}

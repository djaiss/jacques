<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\ServiceCategory;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add a category to the catalogue of a company, after the categories it already
 * has. The catalogue belongs to the whole company, so only somebody allowed to
 * manage services across the company may change it.
 */
class CreateServiceCategory
{
    private ServiceCategory $category;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private ?string $description = null,
    ) {}

    public function execute(): ServiceCategory
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->category;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A service category needs a name');
        }

        $taken = ServiceCategory::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a service category called '.$this->name);
        }
    }

    private function create(): void
    {
        $lastPosition = ServiceCategory::query()
            ->where('company_id', $this->company->id)
            ->max('position');

        $this->category = ServiceCategory::query()->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'description' => $this->description,
            'position' => (int) $lastPosition + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::ServiceCategoryCreated,
            parameters: ['name' => $this->category->name],
        )->onQueue('low');
    }
}

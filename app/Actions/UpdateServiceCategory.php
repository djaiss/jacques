<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ServiceCategory;
use App\Models\User;
use InvalidArgumentException;

/**
 * Rename a service category and describe it afresh. What is passed in replaces
 * what the category had, so a description left out is a description emptied.
 */
class UpdateServiceCategory
{
    public function __construct(
        private readonly User $author,
        private readonly ServiceCategory $category,
        private string $name,
        private ?string $description = null,
    ) {}

    public function execute(): ServiceCategory
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->category;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->category->company)
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
            ->where('company_id', $this->category->company_id)
            ->where('name', $this->name)
            ->whereKeyNot($this->category->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a service category called '.$this->name);
        }
    }

    private function update(): void
    {
        $this->category->name = $this->name;
        $this->category->description = $this->description;
        $this->category->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->category->company,
            user: $this->author,
            action: UserActionEnum::ServiceCategoryUpdated,
            parameters: ['name' => $this->category->name],
        )->onQueue('low');
    }
}

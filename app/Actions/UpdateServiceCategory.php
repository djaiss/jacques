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
    /**
     * @param  array<string, string|null>  $name
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        private readonly User $author,
        private readonly ServiceCategory $category,
        private array $name,
        private array $description = [],
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
        $this->name = array_filter(array_map(TextSanitizer::nullablePlainText(...), $this->name), fn (?string $text): bool => (string) $text !== '');
        $this->description = array_filter(array_map(TextSanitizer::nullablePlainText(...), $this->description), fn (?string $text): bool => (string) $text !== '');
    }

    private function validate(): void
    {
        foreach (array_keys($this->name + $this->description) as $language) {
            if (! in_array($language, $this->category->company->languages, true)) {
                throw new InvalidArgumentException("{$language} is not a language of the company");
            }
        }

        foreach ($this->name as $language => $name) {
            $taken = ServiceCategory::query()
                ->where('company_id', $this->category->company->id)
                ->where("name->{$language}", $name)
                ->whereKeyNot($this->category->id)
                ->exists();

            if ($taken) {
                throw new InvalidArgumentException('The company already has a service category called '.$name);
            }
        }
    }

    private function update(): void
    {
        $removedLanguages = array_diff_key($this->category->name, array_flip($this->category->company->languages));
        $this->category->name = [...$removedLanguages, ...$this->name];

        $removedDescriptions = array_diff_key($this->category->description ?? [], array_flip($this->category->company->languages));
        $description = [...$removedDescriptions, ...$this->description];
        $this->category->description = $description === [] ? null : $description;
        $this->category->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->category->company,
            user: $this->author,
            action: UserActionEnum::ServiceCategoryUpdated,
            parameters: ['name' => $this->name[$this->category->company->languages[0]] ?? __('Untitled')],
        )->onQueue('low');
    }
}

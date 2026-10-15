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

    /**
     * @param  array<string, string>  $name
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private array $name,
        private array $description = [],
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
        $this->name = array_map(TextSanitizer::plainText(...), $this->name);
        $this->description = array_map(TextSanitizer::nullablePlainText(...), $this->description);
    }

    private function validate(): void
    {
        foreach (array_keys($this->name + $this->description) as $language) {
            if (! in_array($language, $this->company->languages, true)) {
                throw new InvalidArgumentException("{$language} is not a language of the company");
            }
        }

        foreach ($this->company->languages as $language) {
            if (($this->name[$language] ?? '') === '') {
                throw new InvalidArgumentException("A name is needed in {$language}");
            }

            $taken = ServiceCategory::query()
                ->where('company_id', $this->company->id)
                ->where("name->{$language}", $this->name[$language])
                ->exists();

            if ($taken) {
                throw new InvalidArgumentException('The company already has a service category called '.$this->name[$language]);
            }
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
            'description' => $this->description === [] ? null : $this->description,
            'position' => (int) $lastPosition + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::ServiceCategoryCreated,
            parameters: ['name' => $this->name[$this->company->languages[0]]],
        )->onQueue('low');
    }
}

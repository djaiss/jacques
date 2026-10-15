<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Add a service to the catalogue of a company, with its steps in the order they
 * are performed, after the services already in its category. A text or a price
 * may still be missing. The service is offered nowhere until a location adds it.
 */
class CreateService
{
    private Service $service;

    /**
     * @param  array<string, string|null>  $name
     * @param  array<string, int>  $prices
     * @param  list<array{name?: array<string, string|null>, duration: int, requires_staff: bool}>  $steps
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private array $name,
        private readonly array $prices,
        private array $steps,
        private array $description = [],
        private readonly ?ServiceCategory $category = null,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();

        DB::transaction(function (): void {
            $this->create();
            $this->createSteps();
        });

        $this->log();

        return $this->service;
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
        $this->name = array_filter(array_map(TextSanitizer::nullablePlainText(...), $this->name), fn (?string $text): bool => (string) $text !== '');
        $this->description = array_filter(array_map(TextSanitizer::nullablePlainText(...), $this->description), fn (?string $text): bool => (string) $text !== '');

        foreach ($this->steps as $index => $step) {
            $this->steps[$index]['name'] = array_filter(array_map(TextSanitizer::nullablePlainText(...), $step['name'] ?? []), fn (?string $text): bool => (string) $text !== '');
        }
    }

    private function validate(): void
    {
        if ($this->category !== null && $this->category->company_id !== $this->company->id) {
            throw new ModelNotFoundException('Service category not found');
        }

        $stepNames = array_merge([], ...array_map(fn (array $step): array => $step['name'], $this->steps));

        foreach (array_keys($this->name + $this->description + $stepNames) as $language) {
            if (! in_array($language, $this->company->languages, true)) {
                throw new InvalidArgumentException("{$language} is not a language of the company");
            }
        }

        foreach ($this->name as $language => $name) {
            $taken = Service::query()
                ->where('company_id', $this->company->id)
                ->where("name->{$language}", $name)
                ->exists();

            if ($taken) {
                throw new InvalidArgumentException('The company already has a service called '.$name);
            }
        }

        foreach ($this->prices as $currency => $amount) {
            if (! in_array($currency, $this->company->currencies, true)) {
                throw new InvalidArgumentException("{$currency} is not a currency of the company");
            }

            if ($amount < 0) {
                throw new InvalidArgumentException('A price cannot be negative');
            }
        }

        if ($this->steps === []) {
            throw new InvalidArgumentException('A service needs at least one step');
        }

        foreach ($this->steps as $step) {
            if ($step['duration'] < 1) {
                throw new InvalidArgumentException('A step lasts at least one minute');
            }
        }

        if (! in_array(true, array_column($this->steps, 'requires_staff'), true)) {
            throw new InvalidArgumentException('A service needs at least one step that requires a team member');
        }
    }

    private function create(): void
    {
        $lastPosition = Service::query()
            ->where('company_id', $this->company->id)
            ->where('service_category_id', $this->category?->id)
            ->max('position');

        $this->service = Service::query()->create([
            'company_id' => $this->company->id,
            'service_category_id' => $this->category?->id,
            'name' => $this->name,
            'description' => $this->description === [] ? null : $this->description,
            'prices' => $this->prices,
            'is_active' => true,
            'position' => (int) $lastPosition + 1,
        ]);
    }

    private function createSteps(): void
    {
        foreach ($this->steps as $index => $step) {
            ServiceStep::query()->create([
                'service_id' => $this->service->id,
                'position' => $index + 1,
                'name' => $step['name'] === [] ? null : $step['name'],
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
            action: UserActionEnum::ServiceCreated,
            parameters: ['name' => $this->name[$this->company->languages[0]] ?? __('Untitled')],
        )->onQueue('low');
    }
}

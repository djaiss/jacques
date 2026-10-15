<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Change a service of the catalogue. What is passed in replaces what the service
 * had, steps included, so a step left out is a step removed. A text or a price
 * may still be missing. Texts written in a language the salon no longer uses are
 * kept, for the day it uses it again. A service moved to another category goes
 * after the services already there.
 */
class UpdateService
{
    /**
     * @param  array<string, string|null>  $name
     * @param  array<string, int>  $prices
     * @param  list<array{name?: array<string, string|null>, duration: int, requires_staff: bool}>  $steps
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
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
            $this->update();
            $this->replaceSteps();
        });

        $this->log();

        return $this->service;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->service->company)
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
        if ($this->category !== null && $this->category->company_id !== $this->service->company_id) {
            throw new ModelNotFoundException('Service category not found');
        }

        $stepNames = array_merge([], ...array_map(fn (array $step): array => $step['name'], $this->steps));

        foreach (array_keys($this->name + $this->description + $stepNames) as $language) {
            if (! in_array($language, $this->service->company->languages, true)) {
                throw new InvalidArgumentException("{$language} is not a language of the company");
            }
        }

        foreach ($this->name as $language => $name) {
            $taken = Service::query()
                ->where('company_id', $this->service->company->id)
                ->where("name->{$language}", $name)
                ->whereKeyNot($this->service->id)
                ->exists();

            if ($taken) {
                throw new InvalidArgumentException('The company already has a service called '.$name);
            }
        }

        foreach ($this->prices as $currency => $amount) {
            if (! in_array($currency, $this->service->company->currencies, true)) {
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

    private function update(): void
    {
        if ($this->service->service_category_id !== $this->category?->id) {
            Service::query()
                ->where('company_id', $this->service->company_id)
                ->where('service_category_id', $this->service->service_category_id)
                ->where('position', '>', $this->service->position)
                ->decrement('position');

            $lastPosition = Service::query()
                ->where('company_id', $this->service->company_id)
                ->where('service_category_id', $this->category?->id)
                ->max('position');

            $this->service->service_category_id = $this->category?->id;
            $this->service->position = (int) $lastPosition + 1;
        }

        $removedLanguages = array_diff_key($this->service->name, array_flip($this->service->company->languages));
        $this->service->name = [...$removedLanguages, ...$this->name];

        $removedDescriptions = array_diff_key($this->service->description ?? [], array_flip($this->service->company->languages));
        $description = [...$removedDescriptions, ...$this->description];
        $this->service->description = $description === [] ? null : $description;
        $removedCurrencies = array_diff_key($this->service->prices, array_flip($this->service->company->currencies));
        $this->service->prices = [...$removedCurrencies, ...$this->prices];
        $this->service->save();
    }

    private function replaceSteps(): void
    {
        $this->service->steps()->delete();

        foreach ($this->steps as $index => $step) {
            ServiceStep::query()->create([
                'service_id' => $this->service->id,
                'position' => $index + 1,
                'name' => $step['name'] === [] ? null : $step['name'],
                'duration' => $step['duration'],
                'requires_staff' => $step['requires_staff'],
            ]);
        }

        $this->service->unsetRelation('steps');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServiceUpdated,
            parameters: ['name' => $this->name[$this->service->company->languages[0]] ?? __('Untitled')],
        )->onQueue('low');
    }
}

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
 * had, steps included, so a step left out is a step removed. Texts written in a
 * language the salon no longer uses are kept, for the day it uses it again. A service moved to
 * another category goes after the services already there.
 */
class UpdateService
{
    /**
     * @param  array<string, string>  $name
     * @param  list<array{active_duration: int, processing_duration: int}>  $steps
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
        private array $name,
        private readonly int $price,
        private readonly array $steps,
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
        $this->name = array_map(TextSanitizer::plainText(...), $this->name);
        $this->description = array_map(TextSanitizer::nullablePlainText(...), $this->description);
    }

    private function validate(): void
    {
        if ($this->category !== null && $this->category->company_id !== $this->service->company_id) {
            throw new ModelNotFoundException('Service category not found');
        }

        foreach (array_keys($this->name + $this->description) as $language) {
            if (! in_array($language, $this->service->company->languages, true)) {
                throw new InvalidArgumentException("{$language} is not a language of the company");
            }
        }

        foreach ($this->service->company->languages as $language) {
            if (($this->name[$language] ?? '') === '') {
                throw new InvalidArgumentException("A name is needed in {$language}");
            }

            $taken = Service::query()
                ->where('company_id', $this->service->company->id)
                ->where("name->{$language}", $this->name[$language])
                ->whereKeyNot($this->service->id)
                ->exists();

            if ($taken) {
                throw new InvalidArgumentException('The company already has a service called '.$this->name[$language]);
            }
        }

        if ($this->price < 0) {
            throw new InvalidArgumentException('A price cannot be negative');
        }

        if ($this->steps === []) {
            throw new InvalidArgumentException('A service needs at least one step');
        }

        foreach ($this->steps as $step) {
            if ($step['active_duration'] < 1) {
                throw new InvalidArgumentException('The active part of a step lasts at least one minute');
            }

            if ($step['processing_duration'] < 0) {
                throw new InvalidArgumentException('The processing part of a step cannot be negative');
            }
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
        $this->service->price = $this->price;
        $this->service->save();
    }

    private function replaceSteps(): void
    {
        $this->service->steps()->delete();

        foreach ($this->steps as $index => $step) {
            ServiceStep::query()->create([
                'service_id' => $this->service->id,
                'position' => $index + 1,
                'active_duration' => $step['active_duration'],
                'processing_duration' => $step['processing_duration'],
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
            parameters: ['name' => $this->name[$this->service->company->languages[0]]],
        )->onQueue('low');
    }
}

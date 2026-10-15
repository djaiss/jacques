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
 * are performed, after the services already in its category. The service is
 * offered nowhere until a location adds it.
 */
class CreateService
{
    private Service $service;

    /**
     * @param  array<string, string>  $name
     * @param  list<array{active_duration: int, processing_duration: int}>  $steps
     * @param  array<string, string|null>  $description
     */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
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
        $this->name = array_map(TextSanitizer::plainText(...), $this->name);
        $this->description = array_map(TextSanitizer::nullablePlainText(...), $this->description);
    }

    private function validate(): void
    {
        if ($this->category !== null && $this->category->company_id !== $this->company->id) {
            throw new ModelNotFoundException('Service category not found');
        }

        foreach (array_keys($this->name + $this->description) as $language) {
            if (! in_array($language, $this->company->languages, true)) {
                throw new InvalidArgumentException("{$language} is not a language of the company");
            }
        }

        foreach ($this->company->languages as $language) {
            if (($this->name[$language] ?? '') === '') {
                throw new InvalidArgumentException("A name is needed in {$language}");
            }

            $taken = Service::query()
                ->where('company_id', $this->company->id)
                ->where("name->{$language}", $this->name[$language])
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
            'price' => $this->price,
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
                'active_duration' => $step['active_duration'],
                'processing_duration' => $step['processing_duration'],
            ]);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::ServiceCreated,
            parameters: ['name' => $this->name[$this->company->languages[0]]],
        )->onQueue('low');
    }
}

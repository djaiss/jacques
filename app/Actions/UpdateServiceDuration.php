<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\User;
use InvalidArgumentException;

/**
 * Change how long a service made of a single step lasts. A service made of
 * several steps lasts the sum of them, so it changes through its steps instead.
 */
class UpdateServiceDuration
{
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
        private readonly int $duration,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->validate();
        $this->update();
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

    private function validate(): void
    {
        if ($this->service->steps->count() !== 1) {
            throw new InvalidArgumentException('Only a service made of a single step has a duration of its own');
        }

        if ($this->duration < 1) {
            throw new InvalidArgumentException('A step lasts at least one minute');
        }
    }

    private function update(): void
    {
        $step = $this->service->steps->first();
        $step->duration = $this->duration;
        $step->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServiceDurationUpdated,
            parameters: ['name' => $this->service->localizedName($this->service->company->languages[0]) ?: __('Untitled')],
        )->onQueue('low');
    }
}

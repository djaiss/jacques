<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Move a service to another place in its category, the services without a
 * category counting as one group. The services between its old and its new
 * place shift by one to make room, so positions keep running from 1.
 */
class UpdateServicePosition
{
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
        private readonly int $position,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            $this->move();
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

    private function validate(): void
    {
        $count = Service::query()
            ->where('company_id', $this->service->company_id)
            ->where('service_category_id', $this->service->service_category_id)
            ->count();

        if ($this->position < 1 || $this->position > $count) {
            throw new InvalidArgumentException('A service can only move to a position between 1 and '.$count);
        }
    }

    private function move(): void
    {
        $siblings = Service::query()
            ->where('company_id', $this->service->company_id)
            ->where('service_category_id', $this->service->service_category_id)
            ->whereKeyNot($this->service->id);

        if ($this->position < $this->service->position) {
            $siblings->whereBetween('position', [$this->position, $this->service->position - 1])->increment('position');
        }

        if ($this->position > $this->service->position) {
            $siblings->whereBetween('position', [$this->service->position + 1, $this->position])->decrement('position');
        }

        $this->service->position = $this->position;
        $this->service->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServiceMoved,
            parameters: ['name' => $this->service->name],
        )->onQueue('low');
    }
}

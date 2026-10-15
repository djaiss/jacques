<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Move a service category to another place in the catalogue of its company.
 * The categories between its old and its new place shift by one to make room,
 * so positions keep running from 1 without a gap.
 */
class UpdateServiceCategoryPosition
{
    public function __construct(
        private readonly User $author,
        private readonly ServiceCategory $category,
        private readonly int $position,
    ) {}

    public function execute(): ServiceCategory
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            $this->move();
        });

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

    private function validate(): void
    {
        $count = ServiceCategory::query()
            ->where('company_id', $this->category->company_id)
            ->count();

        if ($this->position < 1 || $this->position > $count) {
            throw new InvalidArgumentException('A service category can only move to a position between 1 and '.$count);
        }
    }

    private function move(): void
    {
        $siblings = ServiceCategory::query()
            ->where('company_id', $this->category->company_id)
            ->whereKeyNot($this->category->id);

        if ($this->position < $this->category->position) {
            $siblings->whereBetween('position', [$this->position, $this->category->position - 1])->increment('position');
        }

        if ($this->position > $this->category->position) {
            $siblings->whereBetween('position', [$this->category->position + 1, $this->position])->decrement('position');
        }

        $this->category->position = $this->position;
        $this->category->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->category->company,
            user: $this->author,
            action: UserActionEnum::ServiceCategoryMoved,
            parameters: ['name' => $this->category->localizedName($this->category->company->languages[0])],
        )->onQueue('low');
    }
}

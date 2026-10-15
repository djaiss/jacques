<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Move a staff member to another place in the order of the team. The staff
 * members between their old and their new place shift by one to make room,
 * so positions keep running from 1.
 */
class UpdateStaffPosition
{
    public function __construct(
        private readonly User $author,
        private readonly Staff $staff,
        private readonly int $position,
    ) {}

    public function execute(): Staff
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            $this->move();
        });

        $this->log();

        return $this->staff;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::StaffUpdate)
            ->forStaff($this->staff)
            ->authorize();
    }

    private function validate(): void
    {
        $count = Staff::query()
            ->where('company_id', $this->staff->company_id)
            ->count();

        if ($this->position < 1 || $this->position > $count) {
            throw new InvalidArgumentException("A staff member can only move to a position between 1 and {$count}");
        }
    }

    private function move(): void
    {
        $colleagues = Staff::query()
            ->where('company_id', $this->staff->company_id)
            ->whereKeyNot($this->staff->id);

        if ($this->position < $this->staff->position) {
            $colleagues->whereBetween('position', [$this->position, $this->staff->position - 1])->increment('position');
        }

        if ($this->position > $this->staff->position) {
            $colleagues->whereBetween('position', [$this->staff->position + 1, $this->position])->decrement('position');
        }

        $this->staff->position = $this->position;
        $this->staff->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->staff->company,
            user: $this->author,
            action: UserActionEnum::StaffPositionUpdated,
            parameters: ['name' => $this->staff->name],
        )->onQueue('low');
    }
}

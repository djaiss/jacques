<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Give somebody their role, which replaces the one they held (D4). A role only
 * ever reaches the people of the company it belongs to, and the last active
 * owner keeps the Owner role, so a company always keeps somebody who can do
 * everything.
 */
class AssignRole
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
        private readonly Role $role,
    ) {}

    public function execute(): User
    {
        $this->authorize();
        $this->validate();

        DB::transaction(function (): void {
            $this->assign();
        });

        $this->log();

        return $this->user;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->role->company)
            ->authorize();
    }

    private function validate(): void
    {
        if ($this->user->company_id !== $this->role->company_id) {
            throw new ModelNotFoundException('User not found');
        }

        if ($this->role->slug === Role::OWNER) {
            return;
        }

        $owner = $this->user->roles()->where('slug', Role::OWNER)->first();

        if ($owner === null) {
            return;
        }

        $otherActiveOwners = $owner->users()
            ->where('users.id', '!=', $this->user->id)
            ->where('is_active', true)
            ->exists();

        if (! $otherActiveOwners) {
            throw new ModelNotFoundException('Role not found');
        }
    }

    private function assign(): void
    {
        $this->user->roles()->sync([$this->role->id]);
        $this->user->forgetGrants();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->role->company,
            user: $this->author,
            action: UserActionEnum::RoleAssigned,
            parameters: [
                'name' => $this->role->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}

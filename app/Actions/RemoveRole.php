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
 * Take a role away from somebody. Taking away a role they never held changes
 * nothing, and the Owner role is never taken from the last active person
 * holding it, so a company always keeps somebody who can do everything.
 */
class RemoveRole
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
            $this->remove();
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

        if ($this->role->slug !== Role::OWNER) {
            return;
        }

        $otherActiveOwners = $this->role->users()
            ->where('users.id', '!=', $this->user->id)
            ->where('is_active', true)
            ->exists();

        if (! $otherActiveOwners) {
            throw new ModelNotFoundException('Role not found');
        }
    }

    private function remove(): void
    {
        $this->user->roles()->detach($this->role->id);
        $this->user->forgetGrants();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->role->company,
            user: $this->author,
            action: UserActionEnum::RoleRemoved,
            parameters: [
                'name' => $this->role->name,
                'email' => $this->user->email,
            ],
        )->onQueue('low');
    }
}

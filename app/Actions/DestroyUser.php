<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a user. Only an owner of the company may do so, and an owner cannot
 * delete themselves, so the company always keeps at least one owner.
 */
class DestroyUser
{
    public function __construct(
        private readonly User $author,
        private readonly User $user,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::UserDeleted,
            parameters: ['email' => $this->user->email],
        )->onQueue('low');
    }

    private function validate(): void
    {
        if ($this->author->company_id !== $this->user->company_id) {
            throw new ModelNotFoundException('User not found');
        }

        if (! $this->author->isOwner()) {
            throw new ModelNotFoundException('User not found');
        }

        if ($this->author->id === $this->user->id) {
            throw new ModelNotFoundException('User not found');
        }
    }

    private function destroy(): void
    {
        $this->user->delete();
    }
}

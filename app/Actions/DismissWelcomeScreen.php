<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;

/**
 * Mark the company of the user as welcomed, so the welcome screen shown after
 * signing up is never shown to anybody in it again.
 */
class DismissWelcomeScreen
{
    private Company $company;

    private bool $wasPending;

    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): Company
    {
        $this->company = $this->user->company;
        $this->wasPending = $this->company->is_welcome_screen_pending;

        $this->update();
        $this->log();

        return $this->company;
    }

    private function update(): void
    {
        $this->company->is_welcome_screen_pending = false;
        $this->company->save();
    }

    private function log(): void
    {
        if (! $this->wasPending) {
            return;
        }

        LogUserAction::dispatch(
            company: $this->company,
            user: $this->user,
            action: UserActionEnum::WelcomeScreenDismissed,
        )->onQueue('low');
    }
}

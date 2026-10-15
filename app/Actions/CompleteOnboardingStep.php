<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OnboardingStepEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;

/**
 * Record that the company finished one step of its onboarding. The saved step
 * only moves forward, and finishing the last step ends the onboarding.
 */
class CompleteOnboardingStep
{
    private Company $company;

    public function __construct(
        private readonly User $author,
        private readonly OnboardingStepEnum $step,
    ) {}

    public function execute(): Company
    {
        $this->company = $this->author->company;

        $this->authorize();

        if (! $this->company->is_onboarding_pending) {
            return $this->company;
        }

        $this->update();
        $this->log();

        return $this->company;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function update(): void
    {
        $next = $this->step->next();

        if ($next === null) {
            $this->company->is_onboarding_pending = false;
            $this->company->save();

            return;
        }

        if ($next->value > $this->company->onboarding_step->value) {
            $this->company->onboarding_step = $next;
            $this->company->save();
        }
    }

    private function log(): void
    {
        if ($this->company->is_onboarding_pending) {
            return;
        }

        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::OnboardingCompleted,
        )->onQueue('low');
    }
}

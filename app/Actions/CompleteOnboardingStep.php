<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OnboardingStepEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;

/**
 * Record that the company finished one step of its onboarding. The saved step
 * only moves forward, and finishing the last step ends the onboarding.
 * Finishing the services step, every time, also offers every service of the
 * catalogue at the headquarters, since those written during the step are
 * offered nowhere yet.
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

        if ($this->step === OnboardingStepEnum::Services) {
            $this->offerServicesAtHeadquarters();
        }

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

    private function offerServicesAtHeadquarters(): void
    {
        $headquarters = Location::query()
            ->where('company_id', $this->company->id)
            ->where('is_headquarters', true)
            ->first();

        if ($headquarters === null) {
            return;
        }

        new OfferServicesAtLocation(location: $headquarters)->execute();
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

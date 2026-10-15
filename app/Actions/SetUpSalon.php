<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Write what the first onboarding step asks: the name of the salon, which
 * renames the company, the languages it works in, and its first location,
 * created once, offering the catalogue, and updated on every later submission.
 */
class SetUpSalon
{
    private Company $company;

    /** @param  list<string>  $languages */
    public function __construct(
        private readonly User $author,
        private readonly string $name,
        private readonly string $country,
        private readonly string $timezone,
        private readonly array $languages,
        private readonly ?string $region = null,
        private readonly ?string $city = null,
        private readonly ?string $postalCode = null,
        private readonly ?string $addressLine1 = null,
        private readonly ?string $phone = null,
    ) {}

    public function execute(): Company
    {
        DB::transaction(function (): void {
            $this->company = Company::query()
                ->lockForUpdate()
                ->findOrFail($this->author->company_id);

            $this->renameCompany();
            $this->updateLanguages();
            $this->saveFirstLocation();
            $this->completeStep();
        });

        return $this->company->refresh();
    }

    private function renameCompany(): void
    {
        $this->company = new UpdateCompany(
            author: $this->author,
            company: $this->company,
            name: $this->name,
            legalName: $this->company->legal_name,
            websiteUrl: $this->company->website_url,
            currency: $this->company->currency,
            billingEmail: $this->company->billing_email,
        )->execute();
    }

    private function updateLanguages(): void
    {
        $this->company = new UpdateCompanyLanguages(
            author: $this->author,
            company: $this->company,
            languages: $this->languages,
        )->execute();
    }

    private function saveFirstLocation(): void
    {
        $location = $this->firstLocation();

        if ($location === null) {
            $location = new CreateLocation(
                author: $this->author,
                company: $this->company,
                name: $this->company->name,
                country: $this->country,
                region: $this->region,
                city: $this->city,
                postalCode: $this->postalCode,
                addressLine1: $this->addressLine1,
                phone: $this->phone,
                timezone: $this->timezone,
                isHeadquarters: true,
            )->execute();

            new OfferServicesAtLocation(location: $location)->execute();

            return;
        }

        new UpdateLocation(
            author: $this->author,
            location: $location,
            name: $this->company->name,
            country: $this->country,
            region: $this->region,
            city: $this->city,
            postalCode: $this->postalCode,
            addressLine1: $this->addressLine1,
            addressLine2: $location->address_line_2,
            phone: $this->phone,
            email: $location->email,
            timezone: $this->timezone,
            isHeadquarters: true,
        )->execute();
    }

    private function firstLocation(): ?Location
    {
        return $this->company->locations()->where('is_headquarters', true)->first()
            ?? $this->company->locations()->where('name', $this->company->name)->first();
    }

    private function completeStep(): void
    {
        new CompleteOnboardingStep(
            author: $this->author,
            step: OnboardingStepEnum::Location,
        )->execute();
    }
}

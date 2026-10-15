<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

/**
 * Copy the catalogue every new salon starts with into a company, once: its
 * categories, services and steps, named in every interface locale and priced
 * in every offered currency, then offer them at the headquarters when the
 * company already has one.
 */
class CreateDefaultCatalogue
{
    public function __construct(
        private Company $company,
    ) {}

    public function execute(): void
    {
        DB::transaction(function (): void {
            $this->company = Company::query()
                ->lockForUpdate()
                ->findOrFail($this->company->id);

            if ($this->company->default_catalogue_created_at !== null) {
                return;
            }

            new CopyDefaultCatalogue(company: $this->company)->execute();

            $this->company->default_catalogue_created_at = now();
            $this->company->save();

            $this->offerAtHeadquarters();
        });
    }

    private function offerAtHeadquarters(): void
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
}

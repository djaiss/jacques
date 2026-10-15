<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use InvalidArgumentException;

/**
 * Set the currencies a salon prices its catalogue in, in the order given, the
 * first one being its main currency. Prices written in a currency that is
 * removed stay saved, so adding it back brings them back.
 */
class UpdateCompanyCurrencies
{
    /** @param  list<string>  $currencies */
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private readonly array $currencies,
    ) {}

    public function execute(): Company
    {
        $this->authorize();
        $this->validate();
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

    private function validate(): void
    {
        if ($this->currencies === []) {
            throw new InvalidArgumentException('A company needs at least one currency');
        }

        foreach ($this->currencies as $currency) {
            if (! in_array($currency, config('jacques.currencies'), true)) {
                throw new InvalidArgumentException("{$currency} is not a currency the catalogue can be priced in");
            }
        }

        if (count(array_unique($this->currencies)) !== count($this->currencies)) {
            throw new InvalidArgumentException('A currency can only be listed once');
        }
    }

    private function update(): void
    {
        $this->company->currencies = array_values($this->currencies);
        $this->company->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::CompanyCurrenciesUpdated,
            parameters: ['currencies' => implode(', ', $this->currencies)],
        )->onQueue('low');
    }
}

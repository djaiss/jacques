<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Service;
use App\Models\User;
use InvalidArgumentException;

/**
 * Set the prices of a service in the currencies of the salon, in the smallest
 * unit of each currency. A currency given no amount loses its price, and the
 * prices of a currency the salon stopped using are kept.
 */
class UpdateServicePrices
{
    /**
     * @param  array<string, int|null>  $prices
     */
    public function __construct(
        private readonly User $author,
        private readonly Service $service,
        private readonly array $prices,
    ) {}

    public function execute(): Service
    {
        $this->authorize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->service;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($this->service->company)
            ->authorize();
    }

    private function validate(): void
    {
        foreach ($this->prices as $currency => $amount) {
            if (! in_array($currency, $this->service->company->currencies, true)) {
                throw new InvalidArgumentException("{$currency} is not a currency of the company");
            }

            if ($amount !== null && $amount < 0) {
                throw new InvalidArgumentException('A price cannot be negative');
            }
        }
    }

    private function update(): void
    {
        $prices = $this->service->prices;

        foreach ($this->prices as $currency => $amount) {
            if ($amount === null) {
                unset($prices[$currency]);

                continue;
            }

            $prices[$currency] = $amount;
        }

        $this->service->prices = $prices;
        $this->service->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->service->company,
            user: $this->author,
            action: UserActionEnum::ServicePricesUpdated,
            parameters: ['name' => $this->service->localizedName($this->service->company->languages[0]) ?: __('Untitled')],
        )->onQueue('low');
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use InvalidArgumentException;

/**
 * Add a location to a company. The company owns the list, and only somebody who
 * may change its settings may add to it.
 */
class CreateLocation
{
    private Location $location;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private string $name,
        private ?string $country = null,
        private ?string $region = null,
        private ?string $city = null,
        private ?string $postalCode = null,
        private ?string $addressLine1 = null,
        private ?string $addressLine2 = null,
        private ?string $phone = null,
        private ?string $email = null,
        private ?string $timezone = null,
    ) {}

    public function execute(): Location
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();

        return $this->location;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->region = TextSanitizer::nullablePlainText($this->region);
        $this->city = TextSanitizer::nullablePlainText($this->city);
        $this->postalCode = TextSanitizer::nullablePlainText($this->postalCode);
        $this->addressLine1 = TextSanitizer::nullablePlainText($this->addressLine1);
        $this->addressLine2 = TextSanitizer::nullablePlainText($this->addressLine2);
        $this->phone = TextSanitizer::nullablePlainText($this->phone);
        $this->email = TextSanitizer::nullablePlainText($this->email);
        $this->timezone = TextSanitizer::nullablePlainText($this->timezone);

        $country = TextSanitizer::nullablePlainText($this->country);
        $this->country = $country === null ? null : mb_strtoupper($country);
    }

    private function validate(): void
    {
        if ($this->name === '') {
            throw new InvalidArgumentException('A location needs a name');
        }

        $taken = Location::query()
            ->where('company_id', $this->company->id)
            ->where('name', $this->name)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a location called '.$this->name);
        }
    }

    private function create(): void
    {
        $this->location = Location::query()->create([
            'company_id' => $this->company->id,
            'name' => $this->name,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'phone' => $this->phone,
            'email' => $this->email,
            'timezone' => $this->timezone,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::LocationCreated,
            parameters: ['name' => $this->location->name],
        )->onQueue('low');
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Change a location of a company. What is passed in replaces what the location
 * had, so a field left out is a field emptied.
 */
class UpdateLocation
{
    public function __construct(
        private readonly User $author,
        private readonly Location $location,
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
        private readonly bool $isHeadquarters = false,
    ) {}

    public function execute(): Location
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->location;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->location->company)
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
            ->where('company_id', $this->location->company_id)
            ->where('name', $this->name)
            ->whereKeyNot($this->location->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already has a location called '.$this->name);
        }

        if ($this->isHeadquarters && $this->location->isArchived()) {
            throw new InvalidArgumentException('A closed location cannot be the headquarters');
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            if ($this->isHeadquarters) {
                Location::query()
                    ->where('company_id', $this->location->company_id)
                    ->whereKeyNot($this->location->id)
                    ->update(['is_headquarters' => false]);

                $this->location->is_headquarters = true;
            }

            $this->location->name = $this->name;
            $this->location->country = $this->country;
            $this->location->region = $this->region;
            $this->location->city = $this->city;
            $this->location->postal_code = $this->postalCode;
            $this->location->address_line_1 = $this->addressLine1;
            $this->location->address_line_2 = $this->addressLine2;
            $this->location->phone = $this->phone;
            $this->location->email = $this->email;
            $this->location->timezone = $this->timezone;
            $this->location->save();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->location->company,
            user: $this->author,
            action: UserActionEnum::LocationUpdated,
            parameters: ['name' => $this->location->name],
        )->onQueue('low');
    }
}

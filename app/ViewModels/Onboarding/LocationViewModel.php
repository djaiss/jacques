<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Location;
use App\Models\User;
use Collator;
use DateTimeZone;
use IntlTimeZone;
use Locale;

class LocationViewModel extends StepViewModel
{
    public function __construct(User $user)
    {
        parent::__construct(user: $user, step: OnboardingStepEnum::Location);
    }

    public function isSetUp(): bool
    {
        return $this->user->company->onboarding_step->value > OnboardingStepEnum::Location->value;
    }

    /** @return array{name: string, addressLine1: string, city: string, region: string, postalCode: string, country: string, phone: string, timezone: string} */
    public function form(): array
    {
        $location = $this->firstLocation();

        return [
            'name' => (string) old('name', $this->user->company->name),
            'addressLine1' => (string) old('address_line_1', $location->address_line_1 ?? ''),
            'city' => (string) old('city', $location->city ?? ''),
            'region' => (string) old('region', $location->region ?? ''),
            'postalCode' => (string) old('postal_code', $location->postal_code ?? ''),
            'country' => (string) old('country', $location->country ?? ''),
            'phone' => (string) old('phone', $location->phone ?? ''),
            'timezone' => (string) old('timezone', $location->timezone ?? ''),
        ];
    }

    /** @return array<string, string> */
    public function countries(): array
    {
        $locale = app()->getLocale();
        $countries = [];

        foreach (DateTimeZone::listIdentifiers() as $identifier) {
            $code = new DateTimeZone($identifier)->getLocation()['country_code'] ?? '??';

            if ($code === '??') {
                continue;
            }

            $countries[$code] = Locale::getDisplayRegion("-{$code}", $locale);
        }

        new Collator($locale)->asort($countries);

        return $countries;
    }

    /** @return array<string, array<int, array{value: string, label: string}>> */
    public function timezonesByCountry(): array
    {
        $locale = app()->getLocale();
        $timezones = [];

        foreach (array_keys($this->countries()) as $code) {
            foreach (DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $code) as $identifier) {
                $city = str_replace('_', ' ', last(explode('/', $identifier)));
                $name = IntlTimeZone::createTimeZone($identifier)->getDisplayName(false, IntlTimeZone::DISPLAY_LONG_GENERIC, $locale);

                $timezones[$code][] = [
                    'value' => $identifier,
                    'label' => "{$name} · {$city}",
                ];
            }
        }

        return $timezones;
    }

    private function firstLocation(): ?Location
    {
        return $this->user->company->locations()->where('is_headquarters', true)->first();
    }
}

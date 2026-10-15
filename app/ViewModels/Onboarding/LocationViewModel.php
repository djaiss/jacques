<?php

declare(strict_types=1);

namespace App\ViewModels\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Location;
use App\Models\LocationService;
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

    /** @return array{name: string, addressLine1: string, city: string, region: string, postalCode: string, country: string, phone: string, timezone: string, languages: list<string>, currencies: list<string>} */
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
            'languages' => array_values((array) old('languages', $this->user->company->languages ?? ['en'])),
            'currencies' => array_values((array) old('currencies', $this->user->company->currencies ?? ['CAD'])),
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

    /** @return list<array{code: string, label: string, flag: string}> */
    public function languages(): array
    {
        $languages = [];

        foreach (config('jacques.content_languages') as $code => $language) {
            $languages[] = [
                'code' => $code,
                'label' => $language['label'],
                'flag' => $language['flag'],
            ];
        }

        return $languages;
    }

    /** @return array<string, list<string>> */
    public function languagesByCountry(): array
    {
        $languages = [];

        foreach (array_keys($this->countries()) as $code) {
            $languages[$code] = config("jacques.country_languages.{$code}", ['en']);
        }

        return $languages;
    }

    /** @return list<array{code: string, label: string}> */
    public function languagesWithTexts(): array
    {
        $company = $this->user->company;
        $texts = [];

        foreach ([...$company->serviceCategories, ...$company->services] as $item) {
            foreach ([$item->name, $item->description ?? []] as $values) {
                foreach ($values as $language => $text) {
                    if ($text !== null && $text !== '') {
                        $texts[$language] = true;
                    }
                }
            }
        }

        $languages = [];

        foreach ($company->languages as $code) {
            if (isset($texts[$code])) {
                $languages[] = [
                    'code' => $code,
                    'label' => config("jacques.content_languages.{$code}.label"),
                ];
            }
        }

        return $languages;
    }

    /** @return array<int, string> */
    public function languageCounters(): array
    {
        $counters = [];

        foreach (range(1, count(config('jacques.content_languages'))) as $count) {
            $counters[$count] = trans_choice(':count language|:count languages', $count);
        }

        return $counters;
    }

    /** @return list<array{code: string, label: string}> */
    public function currencies(): array
    {
        $labels = $this->currencyLabels();
        $currencies = [];

        foreach (config('jacques.currencies') as $code) {
            $currencies[] = [
                'code' => $code,
                'label' => $labels[$code],
            ];
        }

        return $currencies;
    }

    /** @return array<string, string> */
    public function currenciesByCountry(): array
    {
        $currencies = [];

        foreach (array_keys($this->countries()) as $code) {
            $currencies[$code] = config("jacques.country_currencies.{$code}", 'CAD');
        }

        return $currencies;
    }

    /** @return list<array{code: string, label: string}> */
    public function currenciesWithPrices(): array
    {
        $company = $this->user->company;
        $prices = [];

        $locationServices = LocationService::query()
            ->whereHas('location', fn ($query) => $query->where('company_id', $company->id))
            ->whereNotNull('prices')
            ->get();

        foreach ([...$company->services, ...$locationServices] as $item) {
            foreach (array_keys($item->prices ?? []) as $currency) {
                $prices[$currency] = true;
            }
        }

        $labels = $this->currencyLabels();
        $currencies = [];

        foreach ($company->currencies as $code) {
            if (isset($prices[$code])) {
                $currencies[] = [
                    'code' => $code,
                    'label' => $labels[$code],
                ];
            }
        }

        return $currencies;
    }

    /** @return array<int, string> */
    public function currencyCounters(): array
    {
        $counters = [];

        foreach (range(1, count(config('jacques.currencies'))) as $count) {
            $counters[$count] = trans_choice(':count currency|:count currencies', $count);
        }

        return $counters;
    }

    /** @return array<string, string> */
    private function currencyLabels(): array
    {
        return [
            'CAD' => __('Canadian dollar'),
            'USD' => __('US dollar'),
            'EUR' => __('Euro'),
            'GBP' => __('Pound sterling'),
            'CHF' => __('Swiss franc'),
        ];
    }

    private function firstLocation(): ?Location
    {
        return $this->user->company->locations()->where('is_headquarters', true)->first();
    }
}

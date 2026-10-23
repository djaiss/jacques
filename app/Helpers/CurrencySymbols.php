<?php

declare(strict_types=1);

namespace App\Helpers;

class CurrencySymbols
{
    /**
     * Each currency with a symbol nobody can mistake for another currency's,
     * since a salon may price in two kinds of dollars.
     *
     * @param  list<string>  $codes
     * @return list<array{code: string, symbol: string, name: string}>
     */
    public static function describe(array $codes): array
    {
        $currencies = [
            'CAD' => ['symbol' => 'CA$', 'name' => __('Canadian dollar')],
            'USD' => ['symbol' => 'US$', 'name' => __('US dollar')],
            'EUR' => ['symbol' => '€', 'name' => __('Euro')],
            'GBP' => ['symbol' => '£', 'name' => __('Pound sterling')],
            'CHF' => ['symbol' => 'CHF', 'name' => __('Swiss franc')],
        ];

        return array_map(fn (string $code): array => [
            'code' => $code,
            'symbol' => $currencies[$code]['symbol'] ?? $code,
            'name' => $currencies[$code]['name'] ?? $code,
        ], $codes);
    }
}

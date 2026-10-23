<?php

declare(strict_types=1);

use App\Helpers\CurrencySymbols;

it('tells the two dollars apart', function () {
    expect(CurrencySymbols::describe(['CAD', 'USD']))->toEqual([
        ['code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian dollar'],
        ['code' => 'USD', 'symbol' => 'US$', 'name' => 'US dollar'],
    ]);
});

it('falls back to the code of a currency it does not know', function () {
    expect(CurrencySymbols::describe(['JPY']))->toEqual([
        ['code' => 'JPY', 'symbol' => 'JPY', 'name' => 'JPY'],
    ]);
});

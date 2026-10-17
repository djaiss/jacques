<?php

declare(strict_types=1);

use App\Enums\ScopeEnum;

it('names every scope twice', function () {
    foreach (ScopeEnum::cases() as $scope) {
        expect($scope->label())->not->toBeEmpty();
        expect($scope->shortLabel())->not->toBeEmpty();
    }
});

it('keeps the short name to one word', function () {
    expect(ScopeEnum::Self->shortLabel())->toEqual('Self');
    expect(ScopeEnum::Location->shortLabel())->toEqual('Locations');
    expect(ScopeEnum::Company->shortLabel())->toEqual('Company');
});

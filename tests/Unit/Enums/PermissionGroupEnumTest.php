<?php

declare(strict_types=1);

use App\Enums\PermissionGroupEnum;

it('names every group', function () {
    foreach (PermissionGroupEnum::cases() as $group) {
        expect($group->label())->not->toBeEmpty();
    }
});

it('says what every group holds', function () {
    foreach (PermissionGroupEnum::cases() as $group) {
        expect($group->note())->not->toBeEmpty();
    }
});

it('calls the groups what the screen calls them', function () {
    expect(PermissionGroupEnum::Administration->label())->toEqual('Administration');
    expect(PermissionGroupEnum::Locations->label())->toEqual('Locations');
    expect(PermissionGroupEnum::Team->label())->toEqual('Team');
    expect(PermissionGroupEnum::Appointments->label())->toEqual('Appointments');
});

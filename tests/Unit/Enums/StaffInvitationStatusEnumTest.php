<?php

declare(strict_types=1);

use App\Enums\StaffInvitationStatusEnum;

it('names every state', function () {
    foreach (StaffInvitationStatusEnum::cases() as $status) {
        expect($status->label())->not->toBeEmpty();
    }
});

it('lists its label for translation', function () {
    expect(StaffInvitationStatusEnum::Pending->translationKeys())->toBe(['Pending']);
});

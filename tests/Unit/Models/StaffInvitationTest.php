<?php

declare(strict_types=1);

use App\Enums\StaffInvitationStatusEnum;
use App\Models\StaffInvitation;

it('belongs to a staff member', function () {
    $invitation = StaffInvitation::factory()->create();

    expect($invitation->staff()->exists())->toBeTrue();
});

it('belongs to a role', function () {
    $invitation = StaffInvitation::factory()->create();

    expect($invitation->role()->exists())->toBeTrue();
});

it('belongs to the user who prepared it', function () {
    $invitation = StaffInvitation::factory()->create();

    expect($invitation->author()->exists())->toBeTrue();
});

it('keeps no author once their account is deleted', function () {
    $invitation = StaffInvitation::factory()->create();

    $invitation->author->forceDelete();

    expect($invitation->refresh()->author_id)->toBeNull();
});

it('is prepared until it is sent', function () {
    $invitation = StaffInvitation::factory()->make();

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Prepared);
    expect($invitation->isUsable())->toBeFalse();
});

it('is pending once sent and before its link expires', function () {
    $invitation = StaffInvitation::factory()->sent()->make();

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Pending);
    expect($invitation->isUsable())->toBeTrue();
});

it('is expired once its link expires', function () {
    $invitation = StaffInvitation::factory()->expired()->make();

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Expired);
    expect($invitation->isUsable())->toBeFalse();
});

it('is accepted once accepted, even after its link expires', function () {
    $invitation = StaffInvitation::factory()->accepted()->make([
        'expires_at' => now()->subDay(),
    ]);

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Accepted);
    expect($invitation->isUsable())->toBeFalse();
});

it('is revoked once revoked', function () {
    $invitation = StaffInvitation::factory()->revoked()->make();

    expect($invitation->status())->toBe(StaffInvitationStatusEnum::Revoked);
    expect($invitation->isUsable())->toBeFalse();
});

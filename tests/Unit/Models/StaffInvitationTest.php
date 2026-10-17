<?php

declare(strict_types=1);

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

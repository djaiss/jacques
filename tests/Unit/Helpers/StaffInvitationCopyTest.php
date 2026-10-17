<?php

declare(strict_types=1);

use App\Helpers\StaffInvitationCopy;

it('names who invites and to which salon in the subject', function () {
    $copy = new StaffInvitationCopy(inviter: 'Michael Scott', salon: 'Dunder Mifflin', days: 7);

    expect($copy->subject())->toBe('Michael Scott has invited you to join Dunder Mifflin');
    expect($copy->title())->toBe('You’ve been invited to join Dunder Mifflin');
});

it('greets the staff member by their first name', function () {
    $copy = new StaffInvitationCopy(inviter: 'Michael Scott', salon: 'Dunder Mifflin', days: 7);

    expect($copy->greeting('Pam'))->toBe('Hi Pam,');
});

it('names the inviter the salon and the application in the body', function () {
    config(['app.name' => 'Jacques']);
    $copy = new StaffInvitationCopy(inviter: 'Michael Scott', salon: 'Dunder Mifflin', days: 7);

    expect($copy->body())->toBe('Michael Scott has invited you to join the team at Dunder Mifflin on Jacques.');
});

it('says in how many days the invitation expires', function () {
    expect(new StaffInvitationCopy(inviter: 'Michael Scott', salon: 'Dunder Mifflin', days: 7)->expiry())
        ->toBe('This invitation expires in 7 days.');
    expect(new StaffInvitationCopy(inviter: 'Michael Scott', salon: 'Dunder Mifflin', days: 1)->expiry())
        ->toBe('This invitation expires in 1 day.');
});

it('offers one action', function () {
    $copy = new StaffInvitationCopy(inviter: 'Michael Scott', salon: 'Dunder Mifflin', days: 7);

    expect($copy->button())->toBe('Accept invitation');
});

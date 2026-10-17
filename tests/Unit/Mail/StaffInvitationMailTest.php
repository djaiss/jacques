<?php

declare(strict_types=1);

use App\Mail\StaffInvitationMail;

it('has a subject naming who invites and to which salon', function () {
    $mailable = new StaffInvitationMail(
        url: 'https://jacques.test/invitations/abc',
        inviter: 'Michael Scott',
        salon: 'Dunder Mifflin',
        role: 'Staff',
        firstName: 'Pam',
        days: 7,
    );

    expect($mailable->envelope()->subject)->toEqual('Michael Scott has invited you to join Dunder Mifflin');
});

it('shows the salon the role the link and when it expires', function () {
    $mailable = new StaffInvitationMail(
        url: 'https://jacques.test/invitations/abc',
        inviter: 'Michael Scott',
        salon: 'Dunder Mifflin',
        role: 'Staff',
        firstName: 'Pam',
        days: 7,
    );

    $rendered = $mailable->render();

    expect($rendered)->toContain('Hi Pam,');
    expect($rendered)->toContain('Dunder Mifflin');
    expect($rendered)->toContain('Staff');
    expect($rendered)->toContain('https://jacques.test/invitations/abc');
    expect($rendered)->toContain('Accept invitation');
    expect($rendered)->toContain('7 days');
});

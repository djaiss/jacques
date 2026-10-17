<?php

declare(strict_types=1);

use App\Mail\SignInFromNewAddressMail;

it('has a subject', function () {
    config(['app.name' => 'Jacques']);

    $mailable = new SignInFromNewAddressMail(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1');

    expect($mailable->envelope()->subject)->toEqual('A sign-in from a new place on your Jacques account');
});

it('names the account and where the sign in came from', function () {
    $mailable = new SignInFromNewAddressMail(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1');

    $rendered = $mailable->render();

    expect($rendered)->toContain('michael.scott@dundermifflin.com');
    expect($rendered)->toContain('10.0.0.1');
});

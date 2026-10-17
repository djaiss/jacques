<?php

declare(strict_types=1);

use App\Mail\MagicLinkSignInMail;

it('has a subject', function () {
    config(['app.name' => 'Jacques']);

    expect((new MagicLinkSignInMail(ip: '10.0.0.1'))->envelope()->subject)->toEqual('You signed in to Jacques without a password');
});

it('names where the sign in came from', function () {
    expect((new MagicLinkSignInMail(ip: '10.0.0.1'))->render())->toContain('10.0.0.1');
});

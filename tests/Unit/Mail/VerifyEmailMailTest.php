<?php

declare(strict_types=1);

use App\Mail\VerifyEmailMail;

it('has a subject', function () {
    $mailable = new VerifyEmailMail(url: 'https://jacques.test/verify-email/1/abc');

    expect($mailable->envelope()->subject)->toEqual('Confirm your email address');
});

it('carries the link to confirm the address', function () {
    $mailable = new VerifyEmailMail(url: 'https://jacques.test/verify-email/1/abc');

    expect($mailable->render())->toContain('https://jacques.test/verify-email/1/abc');
});

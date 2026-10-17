<?php

declare(strict_types=1);

use App\Mail\MagicLinkCreatedMail;

it('has a subject', function () {
    config(['app.name' => 'Jacques']);

    $mailable = new MagicLinkCreatedMail(url: 'https://jacques.test/magic-link/abc', minutes: 5);

    expect($mailable->envelope()->subject)->toEqual('Your sign-in link for Jacques');
});

it('carries the link and how long it lasts', function () {
    $mailable = new MagicLinkCreatedMail(url: 'https://jacques.test/magic-link/abc', minutes: 5);

    $rendered = $mailable->render();

    expect($rendered)->toContain('https://jacques.test/magic-link/abc');
    expect($rendered)->toContain('5 minutes');
});

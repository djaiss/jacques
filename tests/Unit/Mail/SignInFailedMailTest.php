<?php

declare(strict_types=1);

use App\Mail\SignInFailedMail;

it('has a subject', function () {
    config(['app.name' => 'Jacques']);

    expect((new SignInFailedMail)->envelope()->subject)->toEqual('Failed sign-in attempt on your Jacques account');
});

it('tells the reader what to do about it', function () {
    expect((new SignInFailedMail)->render())->toContain('change your password');
});

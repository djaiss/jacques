<?php

declare(strict_types=1);

it('does not mark the session cookie secure when the application is reached over http', function () {
    $response = $this->get(route('auth.signIn.new'));

    $cookie = $response->getCookie((string) config('session.cookie'));

    expect($cookie)->not->toBeNull();
    expect($cookie->isSecure())->toBeFalse();
});

it('marks the session cookie secure the same way whichever scheme answers', function () {
    $overHttp = $this->get('http://localhost'.route('auth.signIn.new', absolute: false))
        ->getCookie((string) config('session.cookie'));

    $overHttps = $this->get('https://localhost'.route('auth.signIn.new', absolute: false))
        ->getCookie((string) config('session.cookie'));

    expect($overHttp)->not->toBeNull();
    expect($overHttps)->not->toBeNull();
    expect($overHttps->isSecure())->toBe($overHttp->isSecure());
});

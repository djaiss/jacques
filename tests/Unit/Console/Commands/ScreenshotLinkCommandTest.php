<?php

declare(strict_types=1);

use App\Models\User;

it('prints a signed link for an existing user', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->artisan('jacques:screenshot-link', [
        'email' => 'Michael.Scott@dundermifflin.com',
        '--to' => '/settings/administration/locations',
    ])
        ->expectsOutputToContain('http://localhost/screenshot-link?email=michael.scott%40dundermifflin.com')
        ->expectsOutputToContain('The link signs in as michael.scott@dundermifflin.com and stops working in 5 minutes.')
        ->assertSuccessful();
});

it('refuses when screenshot links are switched off', function () {
    config(['jacques.screenshot_link_enabled' => false]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->artisan('jacques:screenshot-link', ['email' => 'michael.scott@dundermifflin.com'])
        ->expectsOutputToContain('JACQUES_SCREENSHOT_LINK_ENABLED=true')
        ->doesntExpectOutputToContain('screenshot-link?')
        ->assertFailed();
});

it('refuses in production', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    $this->app->detectEnvironment(fn (): string => 'production');
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->artisan('jacques:screenshot-link', ['email' => 'michael.scott@dundermifflin.com'])
        ->expectsOutputToContain('outside production')
        ->assertFailed();
});

it('refuses when the instance is not on a development host', function () {
    config(['jacques.screenshot_link_enabled' => true, 'app.url' => 'https://dundermifflin.com']);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->artisan('jacques:screenshot-link', ['email' => 'michael.scott@dundermifflin.com'])
        ->expectsOutputToContain('This instance answers on dundermifflin.com, which is not a development host')
        ->assertFailed();
});

it('refuses an address nobody uses', function () {
    config(['jacques.screenshot_link_enabled' => true]);

    $this->artisan('jacques:screenshot-link', ['email' => 'toby.flenderson@dundermifflin.com'])
        ->expectsOutputToContain('No user of this instance goes by toby.flenderson@dundermifflin.com.')
        ->assertFailed();
});

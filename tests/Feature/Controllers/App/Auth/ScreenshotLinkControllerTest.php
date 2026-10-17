<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth\ScreenshotLinkControllerTest;

use App\Models\User;
use Illuminate\Support\Facades\URL;

it('signs the user in and lands on the requested path', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->get(link(to: '/settings/administration/locations'));

    $response->assertRedirect('/settings/administration/locations');
    $this->assertAuthenticatedAs($michael);
});

it('lands on the root by default', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $response = $this->get(URL::temporarySignedRoute(
        'auth.screenshotLink.show',
        now()->addMinutes(5),
        ['email' => 'michael.scott@dundermifflin.com'],
    ));

    $response->assertRedirect('/');
});

it('replaces the session of somebody already signed in', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $dwight = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

    $response = $this->actingAs($dwight)->get(link(to: '/settings'));

    $response->assertRedirect('/settings');
    $this->assertAuthenticatedAs($michael);
});

it('never redirects to another site', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->get(link(to: '//dundermifflin.com'))->assertRedirect('/');
    $this->get(link(to: '/\\dundermifflin.com'))->assertRedirect('/');
    $this->get(link(to: 'https://dundermifflin.com'))->assertRedirect('/');
    $this->get(link(to: 'settings'))->assertRedirect('/');
});

it('hides the link when screenshot links are switched off', function () {
    config(['jacques.screenshot_link_enabled' => false]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->get(link(to: '/'))->assertNotFound();
    $this->assertGuest();
});

it('hides the link in production', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    $this->app->detectEnvironment(fn (): string => 'production');
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

    $this->get(link(to: '/'))->assertNotFound();
    $this->assertGuest();
});

it('hides the link on a host that is not for development', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    URL::forceRootUrl('https://dundermifflin.com');

    $this->get(link(to: '/'))->assertNotFound();
    $this->assertGuest();
});

it('signs nobody in for an address nobody uses', function () {
    config(['jacques.screenshot_link_enabled' => true]);

    $response = $this->get(URL::temporarySignedRoute(
        'auth.screenshotLink.show',
        now()->addMinutes(5),
        ['email' => 'toby.flenderson@dundermifflin.com', 'to' => '/'],
    ));

    $response->assertNotFound();
    $this->assertGuest();
});

it('refuses an expired link', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    $link = link(to: '/');

    $this->travel(6)->minutes();

    $this->get($link)->assertForbidden();
    $this->assertGuest();
});

it('refuses an altered link', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
    User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);
    $link = link(to: '/settings');

    $this->get(str_replace('michael.scott', 'dwight.schrute', $link))->assertForbidden();
    $this->get(str_replace('%2Fsettings', '%2Fsettings%2Fadministration', $link))->assertForbidden();
    $this->assertGuest();
});

function link(string $to): string
{
    return URL::temporarySignedRoute(
        'auth.screenshotLink.show',
        now()->addMinutes(5),
        ['email' => 'michael.scott@dundermifflin.com', 'to' => $to],
    );
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreenshotLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_the_user_in_and_lands_on_the_requested_path(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->get($this->link(to: '/settings/administration/locations'));

        $response->assertRedirect('/settings/administration/locations');
        $this->assertAuthenticatedAs($michael);
    }

    #[Test]
    public function it_lands_on_the_root_by_default(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->get(URL::temporarySignedRoute(
            'auth.screenshotLink.show',
            now()->addMinutes(5),
            ['email' => 'michael.scott@dundermifflin.com'],
        ));

        $response->assertRedirect('/');
    }

    #[Test]
    public function it_replaces_the_session_of_somebody_already_signed_in(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $dwight = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

        $response = $this->actingAs($dwight)->get($this->link(to: '/settings'));

        $response->assertRedirect('/settings');
        $this->assertAuthenticatedAs($michael);
    }

    #[Test]
    public function it_never_redirects_to_another_site(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->get($this->link(to: '//dundermifflin.com'))->assertRedirect('/');
        $this->get($this->link(to: '/\\dundermifflin.com'))->assertRedirect('/');
        $this->get($this->link(to: 'https://dundermifflin.com'))->assertRedirect('/');
        $this->get($this->link(to: 'settings'))->assertRedirect('/');
    }

    #[Test]
    public function it_hides_the_link_when_screenshot_links_are_switched_off(): void
    {
        config(['jacques.screenshot_link_enabled' => false]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->get($this->link(to: '/'))->assertNotFound();
        $this->assertGuest();
    }

    #[Test]
    public function it_hides_the_link_in_production(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        $this->app->detectEnvironment(fn (): string => 'production');
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->get($this->link(to: '/'))->assertNotFound();
        $this->assertGuest();
    }

    #[Test]
    public function it_hides_the_link_on_a_host_that_is_not_for_development(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        URL::forceRootUrl('https://dundermifflin.com');

        $this->get($this->link(to: '/'))->assertNotFound();
        $this->assertGuest();
    }

    #[Test]
    public function it_signs_nobody_in_for_an_address_nobody_uses(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);

        $response = $this->get(URL::temporarySignedRoute(
            'auth.screenshotLink.show',
            now()->addMinutes(5),
            ['email' => 'toby.flenderson@dundermifflin.com', 'to' => '/'],
        ));

        $response->assertNotFound();
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_an_expired_link(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $link = $this->link(to: '/');

        $this->travel(6)->minutes();

        $this->get($link)->assertForbidden();
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_an_altered_link(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);
        $link = $this->link(to: '/settings');

        $this->get(str_replace('michael.scott', 'dwight.schrute', $link))->assertForbidden();
        $this->get(str_replace('%2Fsettings', '%2Fsettings%2Fadministration', $link))->assertForbidden();
        $this->assertGuest();
    }

    private function link(string $to): string
    {
        return URL::temporarySignedRoute(
            'auth.screenshotLink.show',
            now()->addMinutes(5),
            ['email' => 'michael.scott@dundermifflin.com', 'to' => $to],
        );
    }
}

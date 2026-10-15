<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreenshotLinkCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prints_a_signed_link_for_an_existing_user(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('jacques:screenshot-link', [
            'email' => 'Michael.Scott@dundermifflin.com',
            '--to' => '/settings/administration/locations',
        ])
            ->expectsOutputToContain('http://localhost/screenshot-link?email=michael.scott%40dundermifflin.com')
            ->expectsOutputToContain('The link signs in as michael.scott@dundermifflin.com and stops working in 5 minutes.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_refuses_when_screenshot_links_are_switched_off(): void
    {
        config(['jacques.screenshot_link_enabled' => false]);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('jacques:screenshot-link', ['email' => 'michael.scott@dundermifflin.com'])
            ->expectsOutputToContain('JACQUES_SCREENSHOT_LINK_ENABLED=true')
            ->doesntExpectOutputToContain('screenshot-link?')
            ->assertFailed();
    }

    #[Test]
    public function it_refuses_in_production(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        $this->app->detectEnvironment(fn (): string => 'production');
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('jacques:screenshot-link', ['email' => 'michael.scott@dundermifflin.com'])
            ->expectsOutputToContain('outside production')
            ->assertFailed();
    }

    #[Test]
    public function it_refuses_when_the_instance_is_not_on_a_development_host(): void
    {
        config(['jacques.screenshot_link_enabled' => true, 'app.url' => 'https://dundermifflin.com']);
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('jacques:screenshot-link', ['email' => 'michael.scott@dundermifflin.com'])
            ->expectsOutputToContain('This instance answers on dundermifflin.com, which is not a development host')
            ->assertFailed();
    }

    #[Test]
    public function it_refuses_an_address_nobody_uses(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);

        $this->artisan('jacques:screenshot-link', ['email' => 'toby.flenderson@dundermifflin.com'])
            ->expectsOutputToContain('No user of this instance goes by toby.flenderson@dundermifflin.com.')
            ->assertFailed();
    }
}

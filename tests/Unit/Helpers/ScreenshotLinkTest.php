<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ScreenshotLink;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreenshotLinkTest extends TestCase
{
    #[Test]
    public function it_is_available_when_switched_on_outside_production(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);

        $this->assertTrue(ScreenshotLink::isAvailable());
    }

    #[Test]
    public function it_is_unavailable_when_switched_off(): void
    {
        config(['jacques.screenshot_link_enabled' => false]);

        $this->assertFalse(ScreenshotLink::isAvailable());
    }

    #[Test]
    public function it_is_unavailable_in_production_even_when_switched_on(): void
    {
        config(['jacques.screenshot_link_enabled' => true]);
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->assertFalse(ScreenshotLink::isAvailable());
    }

    #[Test]
    public function it_accepts_development_hosts(): void
    {
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('localhost'));
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('scranton.localhost'));
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('jacques.test'));
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('Jacques.TEST'));
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('127.0.0.1'));
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('::1'));
        $this->assertTrue(ScreenshotLink::isDevelopmentHost('[::1]'));
    }

    #[Test]
    public function it_refuses_other_hosts(): void
    {
        $this->assertFalse(ScreenshotLink::isDevelopmentHost('dundermifflin.com'));
        $this->assertFalse(ScreenshotLink::isDevelopmentHost('test.dundermifflin.com'));
        $this->assertFalse(ScreenshotLink::isDevelopmentHost('localhost.dundermifflin.com'));
        $this->assertFalse(ScreenshotLink::isDevelopmentHost('10.0.0.5'));
        $this->assertFalse(ScreenshotLink::isDevelopmentHost(''));
    }
}

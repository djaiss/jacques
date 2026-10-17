<?php

declare(strict_types=1);

use App\Helpers\ScreenshotLink;

it('is available when switched on outside production', function () {
    config(['jacques.screenshot_link_enabled' => true]);

    expect(ScreenshotLink::isAvailable())->toBeTrue();
});

it('is unavailable when switched off', function () {
    config(['jacques.screenshot_link_enabled' => false]);

    expect(ScreenshotLink::isAvailable())->toBeFalse();
});

it('is unavailable in production even when switched on', function () {
    config(['jacques.screenshot_link_enabled' => true]);
    $this->app->detectEnvironment(fn (): string => 'production');

    expect(ScreenshotLink::isAvailable())->toBeFalse();
});

it('accepts development hosts', function () {
    expect(ScreenshotLink::isDevelopmentHost('localhost'))->toBeTrue();
    expect(ScreenshotLink::isDevelopmentHost('scranton.localhost'))->toBeTrue();
    expect(ScreenshotLink::isDevelopmentHost('jacques.test'))->toBeTrue();
    expect(ScreenshotLink::isDevelopmentHost('Jacques.TEST'))->toBeTrue();
    expect(ScreenshotLink::isDevelopmentHost('127.0.0.1'))->toBeTrue();
    expect(ScreenshotLink::isDevelopmentHost('::1'))->toBeTrue();
    expect(ScreenshotLink::isDevelopmentHost('[::1]'))->toBeTrue();
});

it('refuses other hosts', function () {
    expect(ScreenshotLink::isDevelopmentHost('dundermifflin.com'))->toBeFalse();
    expect(ScreenshotLink::isDevelopmentHost('test.dundermifflin.com'))->toBeFalse();
    expect(ScreenshotLink::isDevelopmentHost('localhost.dundermifflin.com'))->toBeFalse();
    expect(ScreenshotLink::isDevelopmentHost('10.0.0.5'))->toBeFalse();
    expect(ScreenshotLink::isDevelopmentHost(''))->toBeFalse();
});

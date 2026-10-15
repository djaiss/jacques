<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

class ScreenshotLink
{
    public static function isAvailable(): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        return (bool) config('jacques.screenshot_link_enabled');
    }

    public static function isDevelopmentHost(string $host): bool
    {
        $host = mb_strtolower(trim($host, '[]'));

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        return Str::endsWith($host, ['.localhost', '.test']);
    }
}

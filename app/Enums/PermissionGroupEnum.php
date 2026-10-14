<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionGroupEnum: string
{
    case People = 'people';
    case SensitiveData = 'sensitive_data';
    case Administration = 'administration';

    public function label(): string
    {
        return match ($this) {
            self::People => 'People',
            self::SensitiveData => 'Sensitive data',
            self::Administration => 'Administration',
        };
    }

    public function note(): string
    {
        return match ($this) {
            self::People => 'The employee record itself',
            self::SensitiveData => 'Fields kept off the screen for anybody not allowed to see them',
            self::Administration => 'The company and the way access to it is handed out',
        };
    }
}

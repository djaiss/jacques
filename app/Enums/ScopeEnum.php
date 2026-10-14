<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum ScopeEnum: string implements Translatable
{
    case Self = 'self';
    case Location = 'location';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Themselves only',
            self::Location => 'Their locations',
            self::Company => 'Everybody in the company',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Self => 'Self',
            self::Location => 'Locations',
            self::Company => 'Company',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}

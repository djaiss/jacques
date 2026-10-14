<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum LocationScopeEnum: string implements Translatable
{
    case Active = 'active';
    case Archived = 'archived';
    case All = 'all';

    public function segment(): ?string
    {
        return match ($this) {
            self::Active => null,
            self::Archived => 'archived',
            self::All => 'all',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Open',
            self::Archived => 'Archived',
            self::All => 'All',
        };
    }

    public static function fromSegment(?string $segment): self
    {
        return match ($segment) {
            'archived' => self::Archived,
            'all' => self::All,
            default => self::Active,
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}

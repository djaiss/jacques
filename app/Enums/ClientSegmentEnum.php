<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientSegmentEnum: string
{
    case New = 'new';
    case Regular = 'regular';
    case Inactive = 'inactive';
}

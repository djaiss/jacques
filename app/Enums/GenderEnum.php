<?php

declare(strict_types=1);

namespace App\Enums;

enum GenderEnum: string
{
    case Female = 'female';
    case Male = 'male';
    case NonBinary = 'non_binary';
}

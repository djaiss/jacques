<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum StaffInvitationStatusEnum: string implements Translatable
{
    case Prepared = 'prepared';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Prepared',
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatusEnum: string
{
    case Scheduled = 'scheduled';
    case AttendanceConfirmed = 'attendance_confirmed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function countsAsVisit(): bool
    {
        return match ($this) {
            self::Scheduled, self::AttendanceConfirmed => true,
            self::Cancelled, self::NoShow => false,
        };
    }
}

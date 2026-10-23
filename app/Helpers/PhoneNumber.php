<?php

declare(strict_types=1);

namespace App\Helpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumber
{
    /**
     * Get the phone in the international format (E.164), reading a number
     * without prefix as one of the given country, or null when it is not a
     * valid number.
     */
    public static function normalize(string $phone, ?string $country): ?string
    {
        $utility = PhoneNumberUtil::getInstance();

        try {
            $number = $utility->parse($phone, $country === null ? null : mb_strtoupper($country));
        } catch (NumberParseException) {
            return null;
        }

        if (! $utility->isValidNumber($number)) {
            return null;
        }

        return $utility->format($number, PhoneNumberFormat::E164);
    }
}

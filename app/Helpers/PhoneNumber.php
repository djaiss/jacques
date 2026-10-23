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

    /**
     * Get a stored phone written the way people read it, its international
     * format with spaces, or the phone unchanged when it cannot be parsed.
     */
    public static function display(string $phone): string
    {
        $utility = PhoneNumberUtil::getInstance();

        try {
            return $utility->format($utility->parse($phone), PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException) {
            return $phone;
        }
    }
}

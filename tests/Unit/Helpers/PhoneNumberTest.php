<?php

declare(strict_types=1);

use App\Helpers\PhoneNumber;

it('normalizes a phone to the international format', function (string $phone, ?string $country, ?string $normalized) {
    expect(PhoneNumber::normalize($phone, $country))->toBe($normalized);
})->with('phones');

dataset('phones', [
    'national number in the united states' => ['(570) 555-0100', 'US', '+15705550100'],
    'international number without country' => ['+1 570 555 0100', null, '+15705550100'],
    'international number with another country' => ['+1 570 555 0100', 'FR', '+15705550100'],
    'national number in france' => ['06 12 34 56 78', 'FR', '+33612345678'],
    'lower case country' => ['06 12 34 56 78', 'fr', '+33612345678'],
    'text' => ['abc', 'US', null],
    'national number without country' => ['(570) 555-0100', null, null],
    'too short' => ['12', 'US', null],
]);

it('writes a stored phone the way people read it', function (string $phone, string $displayed) {
    expect(PhoneNumber::display($phone))->toBe($displayed);
})->with('stored phones');

dataset('stored phones', [
    'number in the united states' => ['+15705550100', '+1 570-555-0100'],
    'number in france' => ['+33612345678', '+33 6 12 34 56 78'],
    'number that cannot be parsed' => ['abc', 'abc'],
]);

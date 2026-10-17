<?php

declare(strict_types=1);

use App\Enums\TimeFormatEnum;
use Carbon\Carbon;

it('names every format', function () {
    foreach (TimeFormatEnum::cases() as $format) {
        expect($format->label())->not->toBeEmpty();
    }
});

it('writes a time on a twenty four hour clock', function () {
    $time = Carbon::create(2026, 7, 31, 14, 30);

    expect(TimeFormatEnum::TwentyFourHour->format($time))->toEqual('14:30');
});

it('writes a time on a twelve hour clock', function () {
    $time = Carbon::create(2026, 7, 31, 14, 30);

    expect(TimeFormatEnum::TwelveHour->format($time))->toEqual('2:30 PM');
});

it('shows the same afternoon hour written both ways', function () {
    expect(TimeFormatEnum::TwentyFourHour->example())->toEqual('14:00');
    expect(TimeFormatEnum::TwelveHour->example())->toEqual('2:00 PM');
});

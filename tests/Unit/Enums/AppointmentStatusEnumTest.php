<?php

declare(strict_types=1);

use App\Enums\AppointmentStatusEnum;

it('says whether the appointment counts as a visit', function (AppointmentStatusEnum $status, bool $countsAsVisit) {
    expect($status->countsAsVisit())->toBe($countsAsVisit);
})->with('statuses');

dataset('statuses', [
    'scheduled' => [AppointmentStatusEnum::Scheduled, true],
    'attendance confirmed' => [AppointmentStatusEnum::AttendanceConfirmed, true],
    'cancelled' => [AppointmentStatusEnum::Cancelled, false],
    'no show' => [AppointmentStatusEnum::NoShow, false],
]);

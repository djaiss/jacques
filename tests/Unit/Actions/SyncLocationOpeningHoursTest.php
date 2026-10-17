<?php

declare(strict_types=1);

use App\Actions\SyncLocationOpeningHours;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('replaces the week a location is open', function () {
    Queue::fake();

    $scranton = Location::factory()->create(['name' => 'Scranton']);
    LocationOpeningHour::factory()->create(['location_id' => $scranton->id, 'day_of_week' => 0]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

    $result = new SyncLocationOpeningHours(
        author: $michael,
        location: $scranton,
        week: [
            2 => [['start' => '09:00', 'end' => '18:00']],
            6 => [['start' => '09:00', 'end' => '12:00'], ['start' => '13:00', 'end' => '17:00']],
        ],
    )->execute();

    expect($result)->toBeInstanceOf(Location::class);
    expect($scranton->openingHours()->count())->toEqual(3);
    $this->assertDatabaseMissing('location_opening_hours', ['location_id' => $scranton->id, 'day_of_week' => 0]);
    $this->assertDatabaseHas('location_opening_hours', [
        'location_id' => $scranton->id,
        'day_of_week' => 6,
        'start_time' => '13:00:00',
        'end_time' => '17:00:00',
    ]);

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationOpeningHoursUpdated
            && $job->parameters === ['location' => 'Scranton'],
    );
});

it('closes every day given no range', function () {
    Queue::fake();

    $scranton = Location::factory()->create();
    LocationOpeningHour::factory()->create(['location_id' => $scranton->id]);
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

    new SyncLocationOpeningHours(author: $michael, location: $scranton, week: array_fill(0, 7, []))->execute();

    expect($scranton->openingHours()->exists())->toBeFalse();
});

it('throws when a range does not end after it starts', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

    new SyncLocationOpeningHours(author: $michael, location: $scranton, week: [4 => [['start' => '18:00', 'end' => '09:00']]])->execute();
})->throws(InvalidArgumentException::class);

it('throws when two ranges of a day overlap', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

    new SyncLocationOpeningHours(
        author: $michael,
        location: $scranton,
        week: [1 => [['start' => '13:00', 'end' => '18:00'], ['start' => '09:00', 'end' => '14:00']]],
    )->execute();
})->throws(InvalidArgumentException::class);

it('throws when a time is not written as hours and minutes', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

    new SyncLocationOpeningHours(author: $michael, location: $scranton, week: [1 => [['start' => '9am', 'end' => '18:00']]])->execute();
})->throws(InvalidArgumentException::class);

it('throws when the day is not a day of the week', function () {
    $scranton = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

    new SyncLocationOpeningHours(author: $michael, location: $scranton, week: [7 => [['start' => '09:00', 'end' => '18:00']]])->execute();
})->throws(InvalidArgumentException::class);

it('throws when the location belongs to another company', function () {
    $nashua = Location::factory()->create();
    $michael = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);

    new SyncLocationOpeningHours(author: $michael, location: $nashua, week: [])->execute();
})->throws(ModelNotFoundException::class);

it('throws when the author cannot manage the location', function () {
    $scranton = Location::factory()->create();
    $creed = User::factory()->create(['company_id' => $scranton->company_id]);

    new SyncLocationOpeningHours(author: $creed, location: $scranton, week: [])->execute();
})->throws(ModelNotFoundException::class);

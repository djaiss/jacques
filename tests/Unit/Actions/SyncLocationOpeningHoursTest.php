<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SyncLocationOpeningHours;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationOpeningHour;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncLocationOpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_replaces_the_week_a_location_is_open(): void
    {
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

        $this->assertInstanceOf(Location::class, $result);
        $this->assertEquals(3, $scranton->openingHours()->count());
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
    }

    #[Test]
    public function it_closes_every_day_given_no_range(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create();
        LocationOpeningHour::factory()->create(['location_id' => $scranton->id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

        new SyncLocationOpeningHours(author: $michael, location: $scranton, week: array_fill(0, 7, []))->execute();

        $this->assertFalse($scranton->openingHours()->exists());
    }

    #[Test]
    public function it_throws_when_a_range_does_not_end_after_it_starts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

        new SyncLocationOpeningHours(author: $michael, location: $scranton, week: [4 => [['start' => '18:00', 'end' => '09:00']]])->execute();
    }

    #[Test]
    public function it_throws_when_two_ranges_of_a_day_overlap(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

        new SyncLocationOpeningHours(
            author: $michael,
            location: $scranton,
            week: [1 => [['start' => '13:00', 'end' => '18:00'], ['start' => '09:00', 'end' => '14:00']]],
        )->execute();
    }

    #[Test]
    public function it_throws_when_a_time_is_not_written_as_hours_and_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

        new SyncLocationOpeningHours(author: $michael, location: $scranton, week: [1 => [['start' => '9am', 'end' => '18:00']]])->execute();
    }

    #[Test]
    public function it_throws_when_the_day_is_not_a_day_of_the_week(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $scranton = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::LocationManage);

        new SyncLocationOpeningHours(author: $michael, location: $scranton, week: [7 => [['start' => '09:00', 'end' => '18:00']]])->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $nashua = Location::factory()->create();
        $michael = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);

        new SyncLocationOpeningHours(author: $michael, location: $nashua, week: [])->execute();
    }

    #[Test]
    public function it_throws_when_the_author_cannot_manage_the_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $scranton = Location::factory()->create();
        $creed = User::factory()->create(['company_id' => $scranton->company_id]);

        new SyncLocationOpeningHours(author: $creed, location: $scranton, week: [])->execute();
    }
}

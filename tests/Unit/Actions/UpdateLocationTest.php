<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_an_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateLocation(
            author: $author,
            location: $location,
            name: 'Scranton business park',
            country: 'us',
            city: 'Scranton',
            address: '1725 Slough Avenue',
            timezone: 'America/New_York',
        )->execute();

        $location->refresh();

        $this->assertEquals('Scranton business park', $location->name);
        $this->assertEquals('US', $location->country);
        $this->assertEquals('Scranton', $location->city);
        $this->assertEquals('1725 Slough Avenue', $location->address);
        $this->assertEquals('America/New_York', $location->timezone);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationUpdated
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Scranton business park'],
        );
    }

    #[Test]
    public function it_empties_what_is_left_out(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->create(['company_id' => $company->id]);

        new UpdateLocation(author: $author, location: $location, name: 'Utica branch')->execute();

        $location->refresh();

        $this->assertNull($location->country);
        $this->assertNull($location->city);
        $this->assertNull($location->address);
        $this->assertNull($location->timezone);
    }

    #[Test]
    public function it_lets_an_location_keep_its_own_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateLocation(author: $author, location: $location, name: 'Scranton branch', city: 'Scranton')->execute();

        $location->refresh();

        $this->assertEquals('Scranton branch', $location->name);
        $this->assertEquals('Scranton', $location->city);
    }

    #[Test]
    public function it_promotes_an_location_to_headquarters_and_demotes_the_one_that_held_it(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $scranton = Location::factory()->primary()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
        $utica = Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);

        new UpdateLocation(author: $author, location: $utica, name: 'Utica branch', isHeadquarters: true)->execute();

        $this->assertTrue($utica->refresh()->is_headquarters);
        $this->assertFalse($scranton->refresh()->is_headquarters);
    }

    #[Test]
    public function it_leaves_the_headquarters_of_another_company_alone(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);
        $elsewhere = Location::factory()->primary()->create();

        new UpdateLocation(author: $author, location: $location, name: 'Utica branch', isHeadquarters: true)->execute();

        $this->assertTrue($elsewhere->refresh()->is_headquarters);
    }

    #[Test]
    public function it_leaves_the_headquarters_alone_when_the_box_is_not_ticked(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->primary()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateLocation(author: $author, location: $location, name: 'Scranton business park')->execute();

        $this->assertTrue($location->refresh()->is_headquarters);
    }

    #[Test]
    public function it_throws_when_an_archived_location_is_made_the_headquarters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->archived()->create(['company_id' => $company->id, 'name' => 'Nashua branch']);

        new UpdateLocation(author: $author, location: $location, name: 'Nashua branch', isHeadquarters: true)->execute();
    }

    #[Test]
    public function it_throws_when_another_location_of_the_company_already_has_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);
        $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new UpdateLocation(author: $author, location: $location, name: 'Utica branch')->execute();
    }

    #[Test]
    public function it_throws_when_the_name_is_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
        $location = Location::factory()->create(['company_id' => $company->id]);

        new UpdateLocation(author: $author, location: $location, name: '  ')->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $location = Location::factory()->create(['company_id' => $company->id]);

        new UpdateLocation(author: $author, location: $location, name: 'Scranton branch')->execute();
    }
}

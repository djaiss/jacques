<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);

        $location = new CreateLocation(
            author: $author,
            company: $company,
            name: 'Scranton branch',
            country: 'US',
            region: 'Pennsylvania',
            city: 'Scranton',
            postalCode: '18505',
            addressLine1: '1725 Slough Avenue',
            addressLine2: 'Suite 200',
            phone: '570-555-0100',
            email: 'scranton@dundermifflin.com',
            timezone: 'America/New_York',
        )->execute();

        $this->assertInstanceOf(Location::class, $location);
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'company_id' => $company->id,
            'name' => 'Scranton branch',
            'country' => 'US',
            'region' => 'Pennsylvania',
            'city' => 'Scranton',
            'postal_code' => '18505',
            'address_line_1' => '1725 Slough Avenue',
            'address_line_2' => 'Suite 200',
            'phone' => '570-555-0100',
            'email' => 'scranton@dundermifflin.com',
            'timezone' => 'America/New_York',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationCreated
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Scranton branch'],
        );
    }

    #[Test]
    public function it_creates_an_location_with_nothing_but_a_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);

        $location = new CreateLocation(author: $author, company: $company, name: 'Utica branch')->execute();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Utica branch',
            'country' => null,
            'region' => null,
            'city' => null,
            'postal_code' => null,
            'address_line_1' => null,
            'address_line_2' => null,
            'phone' => null,
            'email' => null,
            'timezone' => null,
        ]);
    }

    #[Test]
    public function it_uppercases_the_country_and_strips_html_from_the_name(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);

        $location = new CreateLocation(
            author: $author,
            company: $company,
            name: '<b>Nashua branch</b>',
            country: 'us',
        )->execute();

        $this->assertEquals('Nashua branch', $location->name);
        $this->assertEquals('US', $location->country);
    }

    #[Test]
    public function it_throws_when_the_name_is_blank(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);

        new CreateLocation(author: $author, company: $company, name: '  ')->execute();
    }

    #[Test]
    public function it_throws_when_the_company_already_has_an_location_of_that_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
        Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new CreateLocation(author: $author, company: $company, name: 'Scranton branch')->execute();
    }

    #[Test]
    public function it_lets_another_company_use_the_same_name(): void
    {
        Queue::fake();

        $dunderMifflin = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::LocationManage);
        Location::factory()->create(['company_id' => Company::factory()->create()->id, 'name' => 'Scranton branch']);

        $location = new CreateLocation(author: $author, company: $dunderMifflin, name: 'Scranton branch')->execute();

        $this->assertEquals('Scranton branch', $location->name);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);

        new CreateLocation(author: $author, company: $company, name: 'Scranton branch')->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_adds_a_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        new CreateLocation(author: $michael, company: $company, name: 'Scranton branch')->execute();
    }
}

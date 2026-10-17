<?php

declare(strict_types=1);

use App\Actions\CreateLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('creates an location', function () {
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

    expect($location)->toBeInstanceOf(Location::class);
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
});

it('creates an location with nothing but a name', function () {
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
});

it('uppercases the country and strips html from the name', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);

    $location = new CreateLocation(
        author: $author,
        company: $company,
        name: '<b>Nashua branch</b>',
        country: 'us',
    )->execute();

    expect($location->name)->toEqual('Nashua branch');
    expect($location->country)->toEqual('US');
});

it('throws when the name is blank', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);

    new CreateLocation(author: $author, company: $company, name: '  ')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the company already has an location of that name', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

    new CreateLocation(author: $author, company: $company, name: 'Scranton branch')->execute();
})->throws(InvalidArgumentException::class);

it('lets another company use the same name', function () {
    Queue::fake();

    $dunderMifflin = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $dunderMifflin->id]), PermissionEnum::LocationManage);
    Location::factory()->create(['company_id' => Company::factory()->create()->id, 'name' => 'Scranton branch']);

    $location = new CreateLocation(author: $author, company: $dunderMifflin, name: 'Scranton branch')->execute();

    expect($location->name)->toEqual('Scranton branch');
});

it('throws when the author may not change the company', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);

    new CreateLocation(author: $author, company: $company, name: 'Scranton branch')->execute();
})->throws(ModelNotFoundException::class);

it('throws when a manager adds a location', function () {
    $company = Company::factory()->create();
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    new CreateLocation(author: $michael, company: $company, name: 'Scranton branch')->execute();
})->throws(ModelNotFoundException::class);

it('makes the new location the only headquarters', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $stamford = Location::factory()->create([
        'company_id' => $company->id,
        'name' => 'Stamford branch',
        'is_headquarters' => true,
    ]);

    $scranton = new CreateLocation(
        author: $author,
        company: $company,
        name: 'Scranton branch',
        isHeadquarters: true,
    )->execute();

    expect($scranton->refresh()->is_headquarters)->toBeTrue();
    expect($stamford->refresh()->is_headquarters)->toBeFalse();
});

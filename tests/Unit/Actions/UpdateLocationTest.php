<?php

declare(strict_types=1);

use App\Actions\UpdateLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;

it('updates an location', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

    new UpdateLocation(
        author: $author,
        location: $location,
        name: 'Scranton business park',
        country: 'us',
        region: 'Pennsylvania',
        city: 'Scranton',
        postalCode: '18505',
        addressLine1: '1725 Slough Avenue',
        addressLine2: 'Suite 200',
        phone: '570-555-0100',
        email: 'scranton@dundermifflin.com',
        timezone: 'America/New_York',
    )->execute();

    $location->refresh();

    expect($location->name)->toEqual('Scranton business park');
    expect($location->country)->toEqual('US');
    expect($location->city)->toEqual('Scranton');
    expect($location->region)->toEqual('Pennsylvania');
    expect($location->postal_code)->toEqual('18505');
    expect($location->address_line_1)->toEqual('1725 Slough Avenue');
    expect($location->address_line_2)->toEqual('Suite 200');
    expect($location->phone)->toEqual('570-555-0100');
    expect($location->email)->toEqual('scranton@dundermifflin.com');
    expect($location->timezone)->toEqual('America/New_York');

    Queue::assertPushedOn(
        queue: 'low',
        job: LogUserAction::class,
        callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationUpdated
            && $job->company->id === $company->id
            && $job->user->id === $author->id
            && $job->parameters === ['name' => 'Scranton business park'],
    );
});

it('empties what is left out', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id]);

    new UpdateLocation(author: $author, location: $location, name: 'Utica branch')->execute();

    $location->refresh();

    expect($location->country)->toBeNull();
    expect($location->city)->toBeNull();
    expect($location->region)->toBeNull();
    expect($location->postal_code)->toBeNull();
    expect($location->address_line_1)->toBeNull();
    expect($location->address_line_2)->toBeNull();
    expect($location->phone)->toBeNull();
    expect($location->email)->toBeNull();
    expect($location->timezone)->toBeNull();
});

it('lets an location keep its own name', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

    new UpdateLocation(author: $author, location: $location, name: 'Scranton branch', city: 'Scranton')->execute();

    $location->refresh();

    expect($location->name)->toEqual('Scranton branch');
    expect($location->city)->toEqual('Scranton');
});

it('promotes an location to headquarters and demotes the one that held it', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $scranton = Location::factory()->primary()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
    $utica = Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);

    new UpdateLocation(author: $author, location: $utica, name: 'Utica branch', isHeadquarters: true)->execute();

    expect($utica->refresh()->is_headquarters)->toBeTrue();
    expect($scranton->refresh()->is_headquarters)->toBeFalse();
});

it('leaves the headquarters of another company alone', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);
    $elsewhere = Location::factory()->primary()->create();

    new UpdateLocation(author: $author, location: $location, name: 'Utica branch', isHeadquarters: true)->execute();

    expect($elsewhere->refresh()->is_headquarters)->toBeTrue();
});

it('leaves the headquarters alone when the box is not ticked', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->primary()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

    new UpdateLocation(author: $author, location: $location, name: 'Scranton business park')->execute();

    expect($location->refresh()->is_headquarters)->toBeTrue();
});

it('throws when an archived location is made the headquarters', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->archived()->create(['company_id' => $company->id, 'name' => 'Nashua branch']);

    new UpdateLocation(author: $author, location: $location, name: 'Nashua branch', isHeadquarters: true)->execute();
})->throws(InvalidArgumentException::class);

it('throws when another location of the company already has that name', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);
    $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

    new UpdateLocation(author: $author, location: $location, name: 'Utica branch')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the name is blank', function () {
    $company = Company::factory()->create();
    $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    $location = Location::factory()->create(['company_id' => $company->id]);

    new UpdateLocation(author: $author, location: $location, name: '  ')->execute();
})->throws(InvalidArgumentException::class);

it('throws when the author may not change the company', function () {
    $company = Company::factory()->create();
    $author = User::factory()->create(['company_id' => $company->id]);
    $location = Location::factory()->create(['company_id' => $company->id]);

    new UpdateLocation(author: $author, location: $location, name: 'Scranton branch')->execute();
})->throws(ModelNotFoundException::class);

it('lets a manager update only the locations they are assigned to', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $company->id, 'name' => 'Stamford']);
    $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
    Staff::factory()->create(['company_id' => $michael->company_id, 'user_id' => $michael->id])->locations()->attach($scranton);

    new UpdateLocation(author: $michael, location: $scranton, name: 'Scranton branch')->execute();

    expect($scranton->refresh()->name)->toEqual('Scranton branch');

    new UpdateLocation(author: $michael, location: $stamford, name: 'Stamford branch')->execute();
})->throws(ModelNotFoundException::class);

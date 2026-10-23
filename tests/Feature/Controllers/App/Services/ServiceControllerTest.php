<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Services\ServiceControllerTest;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceStep;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

function owner(array $languages = ['en']): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin', 'languages' => $languages, 'currencies' => ['CAD', 'USD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function staffAt(Location $location, string $name): Staff
{
    $staff = Staff::factory()->create(['company_id' => $location->company_id, 'display_name' => $name, 'is_active' => true]);
    $staff->locations()->attach($location->id);

    return $staff;
}

function service(User $owner, string $name, ?ServiceCategory $category = null, int $duration = 30): Service
{
    $service = Service::factory()->create([
        'company_id' => $owner->company_id,
        'service_category_id' => $category?->id,
        'name' => ['en' => $name],
        'prices' => ['CAD' => 3500, 'USD' => 3000],
        'is_active' => true,
    ]);
    ServiceStep::query()->where('service_id', $service->id)->update(['duration' => $duration]);

    return $service;
}

it('lists the catalogue with its warnings', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $haircuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Haircuts']]);
    $cut = service($michael, "Men's Haircut", $haircuts);
    service($michael, 'Beard Trim', $haircuts);
    $offer = LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $cut->id]);
    $offer->staff()->attach(staffAt($scranton, 'Jim Halpert')->id);

    $response = $this->actingAs($michael)->get(route('services.index'));

    $response->assertStatus(200);
    $response->assertSee('Haircuts');
    $response->assertSee("Men's Haircut");
    $response->assertSee('The only salon');
    $response->assertSee('Not offered anywhere yet');
    $response->assertSee('Jim Halpert');
    $response->assertSee('2 services in 1 category, offered across 1 salon.');
    $response->assertSee(route('services.new'));
});

it('shows the empty state to a company without services', function () {
    $michael = owner();

    $response = $this->actingAs($michael)->get(route('services.index'));

    $response->assertSee('Nothing for clients to book yet.');
    $response->assertSee('No services yet');
    $response->assertSee('Start from a ready-made catalog');
    $response->assertSee(route('services.new'));
    $response->assertDontSee('Find a service');
});

it('shows the empty state without its buttons to somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);

    $response = $this->actingAs($dwight)->get(route('services.index'));

    $response->assertSee('No services yet');
    $response->assertDontSee('Start from a ready-made catalog');
    $response->assertDontSee(route('services.new'));
});

it('leaves archived services out of the list', function () {
    $michael = owner();
    service($michael, 'Balayage');
    $archived = service($michael, 'Perm');
    $archived->update(['is_active' => false]);

    $response = $this->actingAs($michael)->get(route('services.index'));

    $response->assertSee('Balayage');
    $response->assertDontSee('Perm');
});

it('finds a service by its name', function () {
    $michael = owner();
    service($michael, 'Balayage');
    service($michael, 'Updo');

    $response = $this->actingAs($michael)->get(route('services.index', ['q' => 'balay']));

    $response->assertSee('Balayage');
    $response->assertDontSee('Updo');
});

it('says when no service matches', function () {
    $michael = owner();
    service($michael, 'Balayage');

    $response = $this->actingAs($michael)->get(route('services.index', ['q' => 'zzz']));

    $response->assertSee('No service is called that.');
});

it('hides the add buttons from somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);
    Service::factory()->create(['company_id' => $company->id]);

    $response = $this->actingAs($dwight)->get(route('services.index'));

    $response->assertStatus(200);
    $response->assertDontSee(route('services.new'));
});

it('refuses the list to somebody who may not see services', function () {
    $toby = User::factory()->create();

    $this->actingAs($toby)->get(route('services.index'))->assertNotFound();
});

it('opens the page to add a service', function () {
    $michael = owner();
    Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);

    $response = $this->actingAs($michael)->get(route('services.new'));

    $response->assertStatus(200);
    $response->assertSee('Add this service');
    $response->assertSee('Scranton');
});

it('adds a service, offered at a salon by the people ticked there', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $jim = staffAt($scranton, 'Jim Halpert');
    staffAt($scranton, 'Pam Beesly');
    $haircuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

    $response = $this->actingAs($michael)->post(route('services.create'), [
        'name' => ['en' => 'Beard Trim'],
        'description' => ['en' => 'A tidy beard.'],
        'category' => $haircuts->id,
        'prices' => ['CAD' => '15', 'USD' => '12,50'],
        'steps' => [['name' => ['en' => ''], 'duration' => '20', 'requires_staff' => '1']],
        'offers' => [$scranton->id => ['', (string) $jim->id]],
    ]);

    $service = Service::query()->where('company_id', $michael->company_id)->sole();

    $response->assertRedirect(route('services.show', $service->id));
    $response->assertSessionHas('status', 'The service is added.');
    expect($service->name)->toEqual(['en' => 'Beard Trim']);
    expect($service->prices)->toEqual(['CAD' => 1500, 'USD' => 1250]);
    expect($service->service_category_id)->toBe($haircuts->id);
    expect($service->duration)->toBe(20);
    $offer = LocationService::query()->where('service_id', $service->id)->sole();
    expect($offer->staff()->pluck('staff.id')->all())->toEqual([$jim->id]);
});

it('offers a service at a salon with nobody ticked', function () {
    Queue::fake();

    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);

    $this->actingAs($michael)->post(route('services.create'), [
        'name' => ['en' => 'Beard Trim'],
        'steps' => [['duration' => '20', 'requires_staff' => '1']],
        'offers' => [$scranton->id => ['']],
    ]);

    $this->assertDatabaseHas('location_services', ['location_id' => $scranton->id, 'is_active' => true]);
});

it('refuses a service without a name', function () {
    $michael = owner();

    $response = $this->actingAs($michael)->from(route('services.new'))->post(route('services.create'), [
        'name' => ['en' => ''],
        'steps' => [['duration' => '20', 'requires_staff' => '1']],
    ]);

    $response->assertRedirect(route('services.new'));
    $response->assertSessionHasErrors('name.en');
    $this->assertDatabaseCount('services', 0);
});

it('refuses a duration of zero minutes', function () {
    $michael = owner();

    $response = $this->actingAs($michael)->post(route('services.create'), [
        'name' => ['en' => 'Beard Trim'],
        'steps' => [['duration' => '0', 'requires_staff' => '1']],
    ]);

    $response->assertSessionHasErrors('steps.0.duration');
});

it('refuses a category of another company', function () {
    $michael = owner();
    $elsewhere = ServiceCategory::factory()->create();

    $this->actingAs($michael)->post(route('services.create'), [
        'name' => ['en' => 'Beard Trim'],
        'category' => $elsewhere->id,
        'steps' => [['duration' => '20', 'requires_staff' => '1']],
    ])->assertNotFound();
});

it('refuses to add a service for somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);

    $this->actingAs($dwight)->get(route('services.new'))->assertNotFound();
    $this->actingAs($dwight)->post(route('services.create'), [])->assertNotFound();
});

it('shows a service with its salons and sample figures', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Nashua']);
    $balayage = service($michael, 'Balayage');
    $offer = LocationService::factory()->create(['location_id' => $scranton->id, 'service_id' => $balayage->id]);
    $offer->staff()->attach(staffAt($scranton, 'Pam Beesly')->id);
    LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id]);

    $response = $this->actingAs($michael)->get(route('services.show', $balayage->id));

    $response->assertStatus(200);
    $response->assertSee('Balayage');
    $response->assertSee('Sample figures. Real ones arrive with appointments.');
    $response->assertSee('Pam Beesly');
    $response->assertSee('Offered here, but nobody does it yet, so it cannot be booked.');
    $response->assertSee('Not offered here.');
    $response->assertSee('Cannot be booked at Stamford');
    $response->assertSee('Edit this service');
});

it('shows a service without its edit button to somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);
    $balayage = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Balayage']]);

    $response = $this->actingAs($dwight)->get(route('services.show', $balayage->id));

    $response->assertStatus(200);
    $response->assertDontSee('Edit this service');
});

it('returns not found for a service of another company', function () {
    $michael = owner();
    $elsewhere = Service::factory()->create();

    $this->actingAs($michael)->get(route('services.show', $elsewhere->id))->assertNotFound();
    $this->actingAs($michael)->get(route('services.edit', $elsewhere->id))->assertNotFound();
    $this->actingAs($michael)->put(route('services.update', $elsewhere->id), [])->assertNotFound();
});

it('opens the page to edit a service', function () {
    $michael = owner(languages: ['en', 'fr']);
    $balayage = service($michael, 'Balayage');

    $response = $this->actingAs($michael)->get(route('services.edit', $balayage->id));

    $response->assertStatus(200);
    $response->assertSee('Save changes');
    $response->assertSee('Archive this service');
    $response->assertSee('Name (Français)');
});

it('saves a service and stops offering it where it is unticked', function () {
    Queue::fake();

    $michael = owner(languages: ['en', 'fr']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id]);
    $balayage = service($michael, 'Balayage');
    $offer = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $balayage->id, 'prices' => ['CAD' => 19000]]);

    $response = $this->actingAs($michael)->put(route('services.update', $balayage->id), [
        'name' => ['en' => 'Balayage', 'fr' => 'Balayage'],
        'prices' => ['CAD' => '180', 'USD' => ''],
        'steps' => [
            ['name' => ['en' => 'Application'], 'duration' => '45', 'requires_staff' => '1'],
            ['name' => ['en' => 'Processing'], 'duration' => '30', 'requires_staff' => '0'],
        ],
    ]);

    $response->assertRedirect(route('services.show', $balayage->id));
    $response->assertSessionHas('status', 'The service is saved.');
    $balayage->refresh();
    expect($balayage->prices)->toEqual(['CAD' => 18000]);
    expect($balayage->duration)->toBe(75);
    $offer->refresh();
    expect($offer->is_active)->toBeFalse();
    expect($offer->prices)->toEqual(['CAD' => 19000]);
});

it('refuses to edit a service for somebody who may only look', function () {
    $company = Company::factory()->create();
    $dwight = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceView);
    $balayage = Service::factory()->create(['company_id' => $company->id]);

    $this->actingAs($dwight)->get(route('services.edit', $balayage->id))->assertNotFound();
});

<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Services\ServicesViewModelTest;

use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Services\ServicesViewModel;
use Illuminate\Support\Facades\DB;

function owner(): User
{
    $company = Company::factory()->create(['languages' => ['en', 'fr'], 'currencies' => ['CAD', 'USD']]);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function offer(Service $service, Location $location, array $people = []): LocationService
{
    $offer = LocationService::factory()->create(['location_id' => $location->id, 'service_id' => $service->id]);

    foreach ($people as $person) {
        $person->locations()->syncWithoutDetaching([$location->id]);
        $offer->staff()->attach($person->id);
    }

    return $offer;
}

it('counts the catalogue for every salon', function () {
    $michael = owner();
    Location::factory()->count(3)->create(['company_id' => $michael->company_id]);
    $haircuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
    Service::factory()->count(2)->create(['company_id' => $michael->company_id, 'service_category_id' => $haircuts->id]);

    $viewModel = new ServicesViewModel(user: $michael);

    expect($viewModel->lead())->toBe('2 services in 1 category, offered across 3 salons.');
    expect($viewModel->salons())->toHaveCount(4);
    expect($viewModel->salons()[0]['isCurrent'])->toBeTrue();
    expect($viewModel->categories()[0]['count'])->toBe(2);
});

it('tells where a service is offered and by whom', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Scranton']);
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $pam = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Pam Beesly', 'is_active' => true]);
    $balayage = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage'], 'prices' => ['CAD' => 18000]]);
    $updo = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Updo']]);
    $perm = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Perm']]);
    offer($balayage, $scranton, [$pam]);
    offer($balayage, $stamford);
    offer($updo, $stamford);

    $rows = collect((new ServicesViewModel(user: $michael))->categories()[0]['rows'])->keyBy('name');

    expect($rows['Balayage']['salons']['label'])->toBe('All 2 salons');
    expect($rows['Balayage']['people'][0]['name'])->toBe('Pam Beesly');
    expect($rows['Balayage']['prices'])->toBe([['symbol' => 'CA$', 'amount' => '180'], ['symbol' => 'US$', 'amount' => '']]);
    expect($rows['Updo']['nobody'])->toBeTrue();
    expect($rows['Perm']['salons'])->toBe(['label' => 'Not offered anywhere yet', 'names' => '', 'isWarning' => true]);
    expect($perm->exists)->toBeTrue();
});

it('looks at one salon', function () {
    $michael = owner();
    $stamford = Location::factory()->create(['company_id' => $michael->company_id, 'name' => 'Stamford']);
    $karen = Staff::factory()->create(['company_id' => $michael->company_id, 'display_name' => 'Karen Filippelli', 'is_active' => true]);
    $updo = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Updo']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Perm']]);
    offer($updo, $stamford, [$karen]);

    $viewModel = new ServicesViewModel(user: $michael, location: $stamford);
    $rows = collect($viewModel->categories()[0]['rows'])->keyBy('name');

    expect($viewModel->lead())->toBe('1 of 2 services is offered at Stamford, by 1 person.');
    expect($viewModel->peopleHeading())->toBe('People at Stamford');
    expect($rows['Perm']['isDimmed'])->toBeTrue();
    expect($rows['Perm']['notOfferedHere'])->toBe('Not offered at Stamford');
    expect($rows['Updo']['isDimmed'])->toBeFalse();
});

it('finds a service by its name in any language and drops empty categories', function () {
    $michael = owner();
    $colors = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 1]);
    $nails = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 2]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colors->id, 'name' => ['en' => 'Highlights', 'fr' => 'Mèches']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $nails->id, 'name' => ['en' => 'Manicure']]);

    $viewModel = new ServicesViewModel(user: $michael, search: 'mèch');

    expect($viewModel->categories())->toHaveCount(1);
    expect($viewModel->categories()[0]['rows'][0]['name'])->toBe('Highlights');
    expect(new ServicesViewModel(user: $michael, search: 'zzz')->noResults())->toBeTrue();
});

it('leaves out archived services and the services of another company', function () {
    $michael = owner();
    Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);
    Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Perm'], 'is_active' => false]);
    Service::factory()->create(['name' => ['en' => 'Pretzel']]);

    $names = collect((new ServicesViewModel(user: $michael))->categories())->flatMap(fn (array $category): array => array_column($category['rows'], 'name'))->all();

    expect($names)->toBe(['Balayage']);
});

it('reads the catalogue in the same number of queries whatever its size', function () {
    $michael = owner();
    $scranton = Location::factory()->create(['company_id' => $michael->company_id]);
    $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

    $count = function () use ($michael): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $viewModel = new ServicesViewModel(user: $michael->fresh());
        $viewModel->lead();
        $viewModel->salons();
        $viewModel->categories();

        return count(DB::getQueryLog());
    };

    $make = function (int $times) use ($michael, $category, $scranton): void {
        foreach (range(1, $times) as $index) {
            $service = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id, 'position' => $index + 100 * $times]);
            offer($service, $scranton, [Staff::factory()->create(['company_id' => $michael->company_id, 'is_active' => true])]);
        }
    };

    $make(3);
    $few = $count();
    $make(10);

    expect($count())->toBe($few);
});

it('is empty without a category or an active service', function () {
    $michael = owner();

    $viewModel = new ServicesViewModel(user: $michael);

    expect($viewModel->isEmpty())->toBeTrue();
    expect($viewModel->lead())->toBe('Nothing for clients to book yet.');
    expect($viewModel->canLoadReadyMadeCatalogue())->toBeTrue();
});

it('keeps the ready-made catalog away once a service exists, even archived', function () {
    $michael = owner();
    Service::factory()->create(['company_id' => $michael->company_id, 'is_active' => false]);

    $viewModel = new ServicesViewModel(user: $michael);

    expect($viewModel->isEmpty())->toBeTrue();
    expect($viewModel->canLoadReadyMadeCatalogue())->toBeFalse();
});

it('is not empty with a category and no service', function () {
    $michael = owner();
    ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

    expect((new ServicesViewModel(user: $michael))->isEmpty())->toBeFalse();
});

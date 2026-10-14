<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Administration;

use App\Enums\LocationScopeEnum;
use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\ViewModels\Settings\Administration\LocationsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_the_company_keeps(): void
    {
        $user = $this->company();

        Location::factory()->primary()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/New_York']);
        Location::factory()->create(['company_id' => $user->company_id, 'country' => 'US', 'timezone' => 'America/Chicago']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'country' => 'FR', 'timezone' => 'Europe/Paris']);

        $stats = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->stats();

        $this->assertCount(3, $stats);
        $this->assertEquals(2, $stats[0]['value']);
        $this->assertEquals(1, $stats[1]['value']);
        $this->assertEquals(2, $stats[2]['value']);
    }

    #[Test]
    public function it_lists_the_open_locations_by_default(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Scranton branch', $rows[0]['name']);
    }

    #[Test]
    public function it_lists_the_archived_locations_on_their_own(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Archived)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Nashua branch', $rows[0]['name']);
        $this->assertTrue($rows[0]['isArchived']);
    }

    #[Test]
    public function it_lists_every_location_when_asked_for_all_of_them(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id]);
        Location::factory()->archived()->create(['company_id' => $user->company_id]);

        $this->assertCount(2, new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows());
    }

    #[Test]
    public function it_badges_the_headquarters_and_the_archived_ones(): void
    {
        $user = $this->company();

        Location::factory()->primary()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Utica branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Stamford branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows();

        $this->assertEquals('Headquarters', $rows[0]['badge']);
        $this->assertEquals('Archived', $rows[1]['badge']);
        $this->assertEquals('', $rows[2]['badge']);
    }

    #[Test]
    public function it_keeps_the_locations_of_another_company_out(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->create(['name' => 'Stamford branch']);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::All)->rows();

        $this->assertCount(1, $rows);
        $this->assertEquals('Scranton branch', $rows[0]['name']);
    }

    #[Test]
    public function it_searches_the_name_the_city_and_the_country(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Scranton', 'country' => 'US']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Paris location', 'city' => 'Paris', 'country' => 'FR']);

        $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'scran')->rows();
        $byCity = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'paris')->rows();
        $byCountry = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, search: 'FR')->rows();

        $this->assertCount(1, $byName);
        $this->assertEquals('Scranton branch', $byName[0]['name']);
        $this->assertCount(1, $byCity);
        $this->assertCount(1, $byCountry);
        $this->assertEquals('Paris location', $byCountry[0]['name']);
    }

    #[Test]
    public function it_orders_the_locations_by_location_or_by_city(): void
    {
        $user = $this->company();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Akron', 'country' => 'US']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch', 'city' => 'Utica', 'country' => 'US']);

        $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();
        $byPlace = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, sort: 'place')->rows();

        $this->assertEquals('Nashua branch', $byName[0]['name']);
        $this->assertEquals('Scranton branch', $byPlace[0]['name']);
    }

    #[Test]
    public function it_says_which_order_the_list_is_in_and_leads_to_the_other_one(): void
    {
        $user = $this->company();

        $byName = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->sortToggle();
        $byPlace = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active, sort: 'place')->sortToggle();

        $this->assertEquals('Sorted by location', $byName['label']);
        $this->assertEquals(route('settings.locations.index', ['sort' => 'place']), $byName['url']);
        $this->assertEquals('Sorted by city', $byPlace['label']);
        $this->assertEquals(route('settings.locations.index'), $byPlace['url']);
    }

    #[Test]
    public function it_falls_back_when_an_location_has_no_country_and_no_time_zone(): void
    {
        $user = $this->company();

        Location::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Somewhere',
            'address_line_1' => null,
            'country' => null,
            'city' => null,
            'timezone' => null,
        ]);

        $rows = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->rows();

        $this->assertEquals('somewhere unrecorded', $rows[0]['place']);
        $this->assertEquals('same as the company', $rows[0]['timezone']);
    }

    #[Test]
    public function it_hands_the_panel_everything_it_needs_to_edit_an_location(): void
    {
        $user = $this->company();
        $location = Location::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Scranton branch',
            'region' => 'Pennsylvania',
            'postal_code' => '18505',
            'address_line_1' => '1725 Slough Avenue',
            'address_line_2' => 'Suite 200',
            'phone' => '570-555-0100',
            'email' => 'scranton@dundermifflin.com',
        ]);

        $drawer = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->drawer();

        $this->assertArrayHasKey($location->id, $drawer);
        $this->assertEquals('Scranton branch', $drawer[$location->id]['name']);
        $this->assertEquals('Pennsylvania', $drawer[$location->id]['region']);
        $this->assertEquals('18505', $drawer[$location->id]['postalCode']);
        $this->assertEquals('1725 Slough Avenue', $drawer[$location->id]['addressLine1']);
        $this->assertEquals('Suite 200', $drawer[$location->id]['addressLine2']);
        $this->assertEquals('570-555-0100', $drawer[$location->id]['phone']);
        $this->assertEquals('scranton@dundermifflin.com', $drawer[$location->id]['email']);
        $this->assertEquals(route('settings.locations.update', $location->id), $drawer[$location->id]['updateUrl']);
        $this->assertEquals(route('settings.locationArchives.create', $location->id), $drawer[$location->id]['archiveUrl']);
        $this->assertEquals(route('settings.locationArchives.destroy', $location->id), $drawer[$location->id]['restoreUrl']);
    }

    #[Test]
    public function it_says_which_of_the_three_lists_is_being_read(): void
    {
        $user = $this->company();

        $scopes = new LocationsViewModel(user: $user, scope: LocationScopeEnum::Archived)->scopes();

        $this->assertCount(3, $scopes);
        $this->assertFalse($scopes[0]['current']);
        $this->assertTrue($scopes[1]['current']);
        $this->assertEquals(route('settings.locations.index', 'archived'), $scopes[1]['url']);
    }

    #[Test]
    public function it_knows_the_company_has_no_location_at_all(): void
    {
        $user = $this->company();

        $this->assertTrue(new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->companyHasNoLocation());

        Location::factory()->create(['company_id' => $user->company_id]);

        $this->assertFalse(new LocationsViewModel(user: $user, scope: LocationScopeEnum::Active)->companyHasNoLocation());
    }

    private function company(): User
    {
        $company = Company::factory()->create();

        return $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    }

    #[Test]
    public function it_lists_only_the_locations_a_manager_is_assigned_to(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);
        Location::factory()->create(['company_id' => $company->id, 'name' => 'Stamford branch']);
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $michael->locations()->attach($scranton);

        $viewModel = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active);

        $this->assertEquals(['Scranton branch'], array_column($viewModel->rows(), 'name'));
        $this->assertFalse($viewModel->canAddLocations());
    }

    #[Test]
    public function it_lets_somebody_who_manages_every_location_add_one(): void
    {
        $viewModel = new LocationsViewModel(user: $this->company(), scope: LocationScopeEnum::Active);

        $this->assertTrue($viewModel->canAddLocations());
    }

    #[Test]
    public function it_hands_the_panel_who_works_at_each_location_and_who_could(): void
    {
        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id, 'email' => 'michael@dundermifflin.com']), Role::OWNER);
        $dwight = User::factory()->create(['company_id' => $company->id, 'email' => 'dwight@dundermifflin.com']);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight->locations()->attach($scranton);

        $drawer = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active)->drawer();

        $this->assertEquals([[
            'id' => $dwight->id,
            'name' => 'dwight@dundermifflin.com',
            'removeUrl' => route('settings.locationPeople.destroy', [$scranton->id, $dwight->id]),
        ]], $drawer[$scranton->id]['people']);
        $this->assertEquals([['id' => $michael->id, 'name' => 'michael@dundermifflin.com']], $drawer[$scranton->id]['assignable']);
        $this->assertEquals(route('settings.locationPeople.create', $scranton->id), $drawer[$scranton->id]['assignUrl']);
    }

    #[Test]
    public function it_keeps_the_people_of_a_location_from_somebody_who_may_not_hand_out_roles(): void
    {
        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $michael->locations()->attach($scranton);

        $viewModel = new LocationsViewModel(user: $michael, scope: LocationScopeEnum::Active);

        $this->assertFalse($viewModel->canAssignPeople());
        $this->assertSame([], $viewModel->drawer()[$scranton->id]['people']);
        $this->assertSame([], $viewModel->drawer()[$scranton->id]['assignable']);
    }
}

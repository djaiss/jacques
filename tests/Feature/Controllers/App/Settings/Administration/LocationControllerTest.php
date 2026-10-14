<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_locations_of_the_company(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);

        $response = $this->actingAs($user)->get(route('settings.locations.index'));

        $response->assertOk();
        $response->assertSee('Locations');
        $response->assertSee('Add a location');
        $response->assertSee('open locations');
        $response->assertSee('Sorted by location');
        $response->assertSee('Scranton branch');
    }

    #[Test]
    public function it_orders_the_locations_by_city_when_the_path_asks_for_it(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch', 'city' => 'Akron']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch', 'city' => 'Utica']);

        $response = $this->actingAs($user)->get(route('settings.locations.index', ['sort' => 'place']));

        $response->assertOk();
        $response->assertSee('Sorted by city');
        $response->assertSeeInOrder(['Scranton branch', 'Nashua branch']);
    }

    #[Test]
    public function it_keeps_the_archived_locations_off_the_first_list(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $response = $this->actingAs($user)->get(route('settings.locations.index'));

        $response->assertOk();
        $response->assertSee('Scranton branch');
        $response->assertDontSee('Nashua branch');
    }

    #[Test]
    public function it_shows_the_archived_locations_on_a_path_of_their_own(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $response = $this->actingAs($user)->get(route('settings.locations.index', 'archived'));

        $response->assertOk();
        $response->assertSee('Nashua branch');
        $response->assertDontSee('Scranton branch');
    }

    #[Test]
    public function it_shows_every_location_on_the_third_path(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->archived()->create(['company_id' => $user->company_id, 'name' => 'Nashua branch']);

        $response = $this->actingAs($user)->get(route('settings.locations.index', 'all'));

        $response->assertOk();
        $response->assertSee('Scranton branch');
        $response->assertSee('Nashua branch');
    }

    #[Test]
    public function it_narrows_the_list_down_to_what_was_searched_for(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);
        Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Utica branch']);

        $response = $this->actingAs($user)->get(route('settings.locations.index', ['q' => 'utica']));

        $response->assertOk();
        $response->assertSee('Utica branch');
        $response->assertDontSee('Scranton branch');
    }

    #[Test]
    public function it_turns_away_a_scope_it_does_not_know(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->get('settings/administration/locations/somewhere')->assertNotFound();
    }

    #[Test]
    public function it_hides_the_screen_from_somebody_who_may_not_administer_the_company(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('settings.locations.index'))->assertNotFound();
    }

    #[Test]
    public function it_creates_an_location(): void
    {
        Queue::fake();

        $user = $this->administrator();

        $response = $this->actingAs($user)->post(route('settings.locations.create'), [
            'name' => 'Scranton branch',
            'city' => 'Scranton',
            'country' => 'us',
        ]);

        $response->assertRedirect(route('settings.locations.index'));
        $response->assertSessionHas('status', 'The location is added.');

        $this->assertDatabaseHas('locations', [
            'company_id' => $user->company_id,
            'name' => 'Scranton branch',
            'city' => 'Scranton',
            'country' => 'US',
        ]);
    }

    #[Test]
    public function it_sends_the_new_location_messages_to_a_bag_of_their_own(): void
    {
        $user = $this->administrator();

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->post(route('settings.locations.create'), ['name' => '']);

        $response->assertRedirect(route('settings.locations.index'));
        $response->assertSessionHasErrorsIn('createLocation', ['name']);
    }

    #[Test]
    public function it_turns_away_a_country_that_is_not_two_letters(): void
    {
        $user = $this->administrator();

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->post(route('settings.locations.create'), ['name' => 'Scranton branch', 'country' => 'USA']);

        $response->assertSessionHasErrorsIn('createLocation', ['country']);
    }

    #[Test]
    public function it_saves_an_location(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $location = Location::factory()->create(['company_id' => $user->company_id, 'name' => 'Scranton branch']);

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->put(route('settings.locations.update', $location->id), [
                'name' => 'Scranton business park',
                'city' => 'Scranton',
                'country' => 'US',
                'region' => 'Pennsylvania',
                'postal_code' => '18505',
                'address_line_1' => '1725 Slough Avenue',
                'address_line_2' => 'Suite 200',
                'phone' => '570-555-0100',
                'email' => 'scranton@dundermifflin.com',
                'timezone' => 'America/New_York',
                'is_headquarters' => '1',
            ]);

        $response->assertRedirect(route('settings.locations.index'));
        $response->assertSessionHas('status', 'The location is saved.');

        $location->refresh();

        $this->assertEquals('Scranton business park', $location->name);
        $this->assertEquals('1725 Slough Avenue', $location->address_line_1);
        $this->assertEquals('Suite 200', $location->address_line_2);
        $this->assertEquals('scranton@dundermifflin.com', $location->email);
        $this->assertEquals('America/New_York', $location->timezone);
        $this->assertTrue($location->is_headquarters);
    }

    #[Test]
    public function it_goes_back_to_the_list_the_save_was_made_from(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $location = Location::factory()->archived()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->from(route('settings.locations.index', 'archived'))
            ->put(route('settings.locations.update', $location->id), ['name' => 'Nashua branch'])
            ->assertRedirect(route('settings.locations.index', 'archived'));
    }

    #[Test]
    public function it_turns_away_a_time_zone_that_does_not_exist(): void
    {
        $user = $this->administrator();
        $location = Location::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->put(route('settings.locations.update', $location->id), [
                'name' => 'Scranton branch',
                'timezone' => 'Middle/Earth',
            ]);

        $response->assertSessionHasErrors(['timezone']);
    }

    #[Test]
    public function it_turns_away_an_email_that_is_not_an_address(): void
    {
        $user = $this->administrator();
        $location = Location::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->put(route('settings.locations.update', $location->id), [
                'name' => 'Scranton branch',
                'email' => 'dwight at schrute farms',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function it_cannot_save_an_location_of_another_company(): void
    {
        $user = $this->administrator();
        $location = Location::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.locations.update', $location->id), ['name' => 'Stamford branch'])
            ->assertNotFound();
    }

    #[Test]
    public function it_stops_somebody_who_may_not_administer_the_company_from_adding_an_location(): void
    {
        $user = $this->member();

        $this->actingAs($user)
            ->post(route('settings.locations.create'), ['name' => 'Scranton branch'])
            ->assertNotFound();
    }

    private function administrator(): User
    {
        $company = Company::factory()->create();

        return $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::CompanyManage);
    }

    private function member(): User
    {
        $company = Company::factory()->create();

        return $this->makeMember(User::factory()->create(['company_id' => $company->id]));
    }

    #[Test]
    public function it_gives_the_fields_of_the_panel_and_of_the_dialog_different_ids(): void
    {
        $user = $this->administrator();

        Location::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)->get(route('settings.locations.index'));

        $this->assertSame(1, substr_count($response->getContent(), 'id="name"'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="location_name"'));
        $response->assertSee('name="name"', escape: false);
    }
}

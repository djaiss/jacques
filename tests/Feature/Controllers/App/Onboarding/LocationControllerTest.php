<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\ViewModels\Onboarding\LocationViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_salon_form_prefilled_with_the_company_name(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->get(route('onboarding.location.index'));

        $response->assertStatus(200);
        $response->assertSee('Welcome to Dunder Mifflin');
        $response->assertSee('Onboarding · Step 1 of 4');
        $response->assertSee('Set up your salon');
        $response->assertSee('value="Dunder Mifflin"', false);
        $response->assertSee(route('onboarding.location.update'));
        $response->assertSee(route('home.index'));
    }

    #[Test]
    public function it_sets_up_the_salon_and_its_first_location(): void
    {
        Queue::fake();

        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin Scranton',
            'address_line_1' => '1725 Slough Avenue',
            'city' => 'Scranton',
            'region' => 'Pennsylvania',
            'postal_code' => '18503',
            'phone' => '+1 570 555 0100',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'languages' => ['en'],
            'currencies' => ['CAD'],
        ]);

        $response->assertRedirect(route('onboarding.location.index'));

        $company = $user->company->refresh();

        $this->assertEquals('Dunder Mifflin Scranton', $company->name);
        $this->assertEquals(OnboardingStepEnum::Services, $company->onboarding_step);
        $this->assertDatabaseHas('locations', [
            'company_id' => $company->id,
            'name' => 'Dunder Mifflin Scranton',
            'city' => 'Scranton',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'is_headquarters' => true,
        ]);

        $this->actingAs($user)->get(route('onboarding.location.index'))
            ->assertSee('Dunder Mifflin Scranton is ready')
            ->assertSee(route('onboarding.services.index'));
    }

    #[Test]
    public function it_asks_for_a_name(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => '',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'languages' => ['en'],
            'currencies' => ['CAD'],
        ]);

        $response->assertSessionHasErrors(['name' => 'The salon needs a name.']);
        $this->assertEquals(0, Location::query()->count());
    }

    #[Test]
    public function it_refuses_a_time_zone_from_another_country(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin',
            'country' => 'CA',
            'timezone' => 'Europe/Paris',
            'languages' => ['en'],
            'currencies' => ['CAD'],
        ]);

        $response->assertSessionHasErrors('timezone');
        $this->assertEquals(0, Location::query()->count());
    }

    #[Test]
    public function it_updates_the_same_location_when_submitted_again(): void
    {
        Queue::fake();

        $user = $this->owner();
        $payload = ['name' => 'Dunder Mifflin', 'country' => 'US', 'timezone' => 'America/New_York', 'languages' => ['en'], 'currencies' => ['CAD']];

        $this->actingAs($user)->put(route('onboarding.location.update'), $payload);
        $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'city' => 'Scranton']);

        $this->assertEquals(1, Location::query()->where('company_id', $user->company_id)->count());
        $this->assertDatabaseHas('locations', ['company_id' => $user->company_id, 'city' => 'Scranton']);
    }

    #[Test]
    public function it_saves_the_languages_in_the_order_they_were_chosen(): void
    {
        Queue::fake();

        $user = $this->owner();

        $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin Montreal',
            'country' => 'CA',
            'timezone' => 'America/Toronto',
            'languages' => ['fr', 'en', 'es'],
            'currencies' => ['CAD'],
        ])->assertRedirect(route('onboarding.location.index'));

        $this->assertEquals(['fr', 'en', 'es'], $user->company->refresh()->languages);
    }

    #[Test]
    public function it_asks_for_at_least_one_language(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'languages' => [],
            'currencies' => ['CAD'],
        ]);

        $response->assertSessionHasErrors(['languages' => 'Choose at least one language.']);
        $this->assertEquals(0, Location::query()->count());
    }

    #[Test]
    public function it_refuses_an_unknown_language_and_a_language_chosen_twice(): void
    {
        $user = $this->owner();

        $payload = ['name' => 'Dunder Mifflin', 'country' => 'US', 'timezone' => 'America/New_York', 'currencies' => ['CAD']];

        $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'languages' => ['en', 'tlh']])
            ->assertSessionHasErrors('languages.1');
        $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'languages' => ['en', 'en']])
            ->assertSessionHasErrors('languages.0');
    }

    #[Test]
    public function it_shows_the_languages_saved_when_the_salon_is_edited(): void
    {
        Queue::fake();

        $user = $this->owner();

        $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin Montreal',
            'country' => 'CA',
            'timezone' => 'America/Toronto',
            'languages' => ['fr', 'es'],
            'currencies' => ['CAD'],
        ]);

        $response = $this->actingAs($user->refresh())->get(route('onboarding.location.index'));

        $response->assertSee('Languages spoken at the salon');
        $response->assertViewHas('viewModel', fn (LocationViewModel $viewModel): bool => $viewModel->form()['languages'] === ['fr', 'es']);
    }

    #[Test]
    public function it_saves_the_currencies_in_the_order_they_were_chosen(): void
    {
        Queue::fake();

        $user = $this->owner();

        $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin Nashua',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'languages' => ['en'],
            'currencies' => ['USD', 'CAD'],
        ])->assertRedirect(route('onboarding.location.index'));

        $this->assertEquals(['USD', 'CAD'], $user->company->refresh()->currencies);
    }

    #[Test]
    public function it_asks_for_at_least_one_currency(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin',
            'country' => 'US',
            'timezone' => 'America/New_York',
            'languages' => ['en'],
            'currencies' => [],
        ]);

        $response->assertSessionHasErrors(['currencies' => 'Choose at least one currency.']);
        $this->assertEquals(0, Location::query()->count());
    }

    #[Test]
    public function it_refuses_an_unknown_currency_and_a_currency_chosen_twice(): void
    {
        $user = $this->owner();

        $payload = ['name' => 'Dunder Mifflin', 'country' => 'US', 'timezone' => 'America/New_York', 'languages' => ['en']];

        $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'currencies' => ['CAD', 'SCHRUTEBUCK']])
            ->assertSessionHasErrors('currencies.1');
        $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'currencies' => ['CAD', 'CAD']])
            ->assertSessionHasErrors('currencies.0');
    }

    #[Test]
    public function it_shows_the_currencies_saved_when_the_salon_is_edited(): void
    {
        Queue::fake();

        $user = $this->owner();

        $this->actingAs($user)->put(route('onboarding.location.update'), [
            'name' => 'Dunder Mifflin Montreal',
            'country' => 'CA',
            'timezone' => 'America/Toronto',
            'languages' => ['fr'],
            'currencies' => ['CAD', 'USD'],
        ]);

        $response = $this->actingAs($user->refresh())->get(route('onboarding.location.index'));

        $response->assertSee('Currencies accepted at the salon');
        $response->assertViewHas('viewModel', fn (LocationViewModel $viewModel): bool => $viewModel->form()['currencies'] === ['CAD', 'USD']);
    }

    #[Test]
    public function it_sends_a_user_who_is_not_the_owner_home(): void
    {
        $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        $this->actingAs($dwight)->get(route('onboarding.location.index'))->assertRedirect(route('home.index'));
        $this->actingAs($dwight)->put(route('onboarding.location.update'), [])->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_sends_a_company_already_onboarded_home(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

        $this->actingAs($user)->get(route('onboarding.location.index'))->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_shows_the_welcome_screen_first(): void
    {
        $user = $this->owner();
        $user->company->update(['is_welcome_screen_pending' => true]);

        $this->actingAs($user)->get(route('onboarding.location.index'))->assertRedirect(route('welcome.index'));
    }

    #[Test]
    public function it_sends_a_guest_to_sign_in(): void
    {
        $this->get(route('onboarding.location.index'))->assertRedirect(route('auth.signIn.new'));
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}

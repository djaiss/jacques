<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\LocationControllerTest;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\ViewModels\Onboarding\LocationViewModel;
use Illuminate\Support\Facades\Queue;

it('shows the salon form prefilled with the company name', function () {
    $user = owner();

    $response = $this->actingAs($user)->get(route('onboarding.location.index'));

    $response->assertStatus(200);
    $response->assertSee('Welcome to Dunder Mifflin');
    $response->assertSee('Onboarding · Step 1 of 4');
    $response->assertSee('Set up your salon');
    $response->assertSee('value="Dunder Mifflin"', false);
    $response->assertSee(route('onboarding.location.update'));
    $response->assertSee(route('home.index'));
});

it('sets up the salon and its first location', function () {
    Queue::fake();

    $user = owner();

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

    $response->assertRedirect(route('onboarding.services.index'));

    $company = $user->company->refresh();

    expect($company->name)->toEqual('Dunder Mifflin Scranton');
    expect($company->onboarding_step)->toEqual(OnboardingStepEnum::Services);
    $this->assertDatabaseHas('locations', [
        'company_id' => $company->id,
        'name' => 'Dunder Mifflin Scranton',
        'city' => 'Scranton',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'is_headquarters' => true,
    ]);
});

it('asks for a name', function () {
    $user = owner();

    $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
        'name' => '',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'languages' => ['en'],
        'currencies' => ['CAD'],
    ]);

    $response->assertSessionHasErrors(['name' => 'The salon needs a name.']);
    expect(Location::query()->count())->toEqual(0);
});

it('refuses a time zone from another country', function () {
    $user = owner();

    $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
        'name' => 'Dunder Mifflin',
        'country' => 'CA',
        'timezone' => 'Europe/Paris',
        'languages' => ['en'],
        'currencies' => ['CAD'],
    ]);

    $response->assertSessionHasErrors('timezone');
    expect(Location::query()->count())->toEqual(0);
});

it('updates the same location when submitted again', function () {
    Queue::fake();

    $user = owner();
    $payload = ['name' => 'Dunder Mifflin', 'country' => 'US', 'timezone' => 'America/New_York', 'languages' => ['en'], 'currencies' => ['CAD']];

    $this->actingAs($user)->put(route('onboarding.location.update'), $payload);
    $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'city' => 'Scranton']);

    expect(Location::query()->where('company_id', $user->company_id)->count())->toEqual(1);
    $this->assertDatabaseHas('locations', ['company_id' => $user->company_id, 'city' => 'Scranton']);
});

it('saves the languages in the order they were chosen', function () {
    Queue::fake();

    $user = owner();

    $this->actingAs($user)->put(route('onboarding.location.update'), [
        'name' => 'Dunder Mifflin Montreal',
        'country' => 'CA',
        'timezone' => 'America/Toronto',
        'languages' => ['fr', 'en', 'es'],
        'currencies' => ['CAD'],
    ])->assertRedirect(route('onboarding.services.index'));

    expect($user->company->refresh()->languages)->toEqual(['fr', 'en', 'es']);
});

it('asks for at least one language', function () {
    $user = owner();

    $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
        'name' => 'Dunder Mifflin',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'languages' => [],
        'currencies' => ['CAD'],
    ]);

    $response->assertSessionHasErrors(['languages' => 'Choose at least one language.']);
    expect(Location::query()->count())->toEqual(0);
});

it('refuses an unknown language and a language chosen twice', function () {
    $user = owner();

    $payload = ['name' => 'Dunder Mifflin', 'country' => 'US', 'timezone' => 'America/New_York', 'currencies' => ['CAD']];

    $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'languages' => ['en', 'tlh']])
        ->assertSessionHasErrors('languages.1');
    $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'languages' => ['en', 'en']])
        ->assertSessionHasErrors('languages.0');
});

it('shows the languages saved when the salon is edited', function () {
    Queue::fake();

    $user = owner();

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
});

it('saves the currencies in the order they were chosen', function () {
    Queue::fake();

    $user = owner();

    $this->actingAs($user)->put(route('onboarding.location.update'), [
        'name' => 'Dunder Mifflin Nashua',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'languages' => ['en'],
        'currencies' => ['USD', 'CAD'],
    ])->assertRedirect(route('onboarding.services.index'));

    expect($user->company->refresh()->currencies)->toEqual(['USD', 'CAD']);
});

it('asks for at least one currency', function () {
    $user = owner();

    $response = $this->actingAs($user)->put(route('onboarding.location.update'), [
        'name' => 'Dunder Mifflin',
        'country' => 'US',
        'timezone' => 'America/New_York',
        'languages' => ['en'],
        'currencies' => [],
    ]);

    $response->assertSessionHasErrors(['currencies' => 'Choose at least one currency.']);
    expect(Location::query()->count())->toEqual(0);
});

it('refuses an unknown currency and a currency chosen twice', function () {
    $user = owner();

    $payload = ['name' => 'Dunder Mifflin', 'country' => 'US', 'timezone' => 'America/New_York', 'languages' => ['en']];

    $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'currencies' => ['CAD', 'SCHRUTEBUCK']])
        ->assertSessionHasErrors('currencies.1');
    $this->actingAs($user)->put(route('onboarding.location.update'), [...$payload, 'currencies' => ['CAD', 'CAD']])
        ->assertSessionHasErrors('currencies.0');
});

it('shows the currencies saved when the salon is edited', function () {
    Queue::fake();

    $user = owner();

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
});

it('sends a user who is not the owner home', function () {
    $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
    $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

    $this->actingAs($dwight)->get(route('onboarding.location.index'))->assertRedirect(route('home.index'));
    $this->actingAs($dwight)->put(route('onboarding.location.update'), [])->assertRedirect(route('home.index'));
});

it('sends a company already onboarded home', function () {
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
    $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);

    $this->actingAs($user)->get(route('onboarding.location.index'))->assertRedirect(route('home.index'));
});

it('shows the welcome screen first', function () {
    $user = owner();
    $user->company->update(['is_welcome_screen_pending' => true]);

    $this->actingAs($user)->get(route('onboarding.location.index'))->assertRedirect(route('welcome.index'));
});

it('sends a guest to sign in', function () {
    $this->get(route('onboarding.location.index'))->assertRedirect(route('auth.signIn.new'));
});

function owner(): User
{
    $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
    $user = User::factory()->create(['company_id' => $company->id]);

    return test()->giveDefaultRole($user, Role::OWNER);
}

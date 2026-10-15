<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultCatalogue;
use App\Actions\CreateLocation;
use App\Actions\SetUpSalon;
use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetUpSalonTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_the_company_and_creates_its_first_location(): void
    {
        Queue::fake();

        $user = $this->owner();

        $company = new SetUpSalon(
            author: $user,
            name: 'Dunder Mifflin Scranton',
            country: 'US',
            timezone: 'America/New_York',
            languages: ['en'],
            region: 'Pennsylvania',
            city: 'Scranton',
            postalCode: '18503',
            addressLine1: '1725 Slough Avenue',
            phone: '+1 570 555 0100',
        )->execute();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Dunder Mifflin Scranton', $company->name);
        $this->assertEquals('Dunder Mifflin Inc.', $company->legal_name);
        $this->assertEquals(OnboardingStepEnum::Services, $company->onboarding_step);
        $this->assertDatabaseHas('locations', [
            'company_id' => $company->id,
            'name' => 'Dunder Mifflin Scranton',
            'country' => 'US',
            'region' => 'Pennsylvania',
            'city' => 'Scranton',
            'postal_code' => '18503',
            'address_line_1' => '1725 Slough Avenue',
            'phone' => '+1 570 555 0100',
            'timezone' => 'America/New_York',
            'is_headquarters' => true,
        ]);
    }

    #[Test]
    public function it_saves_the_languages_of_the_salon(): void
    {
        Queue::fake();

        $user = $this->owner();

        $company = new SetUpSalon(
            author: $user,
            name: 'Dunder Mifflin Montreal',
            country: 'CA',
            timezone: 'America/Toronto',
            languages: ['fr', 'en'],
        )->execute();

        $this->assertEquals(['fr', 'en'], $company->languages);
    }

    #[Test]
    public function it_updates_the_same_location_when_submitted_again(): void
    {
        Queue::fake();

        $user = $this->owner();

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();

        $location = Location::query()->where('company_id', $user->company_id)->firstOrFail();
        $location->update(['email' => 'scranton@dundermifflin.com', 'address_line_2' => 'Suite 200']);

        new SetUpSalon(
            author: $user,
            name: 'Dunder Mifflin Scranton',
            country: 'US',
            timezone: 'America/Chicago',
            languages: ['en'],
            city: 'Scranton',
        )->execute();

        $this->assertEquals(1, Location::query()->where('company_id', $user->company_id)->count());

        $location->refresh();

        $this->assertEquals('Dunder Mifflin Scranton', $location->name);
        $this->assertEquals('America/Chicago', $location->timezone);
        $this->assertEquals('Scranton', $location->city);
        $this->assertEquals('scranton@dundermifflin.com', $location->email);
        $this->assertEquals('Suite 200', $location->address_line_2);
    }

    #[Test]
    public function it_reuses_a_location_already_named_like_the_salon(): void
    {
        Queue::fake();

        $user = $this->owner();
        Location::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Dunder Mifflin',
            'is_headquarters' => false,
        ]);

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();

        $this->assertEquals(1, Location::query()->where('company_id', $user->company_id)->count());
        $this->assertTrue(Location::query()->where('company_id', $user->company_id)->firstOrFail()->is_headquarters);
    }

    #[Test]
    public function it_refuses_a_user_who_cannot_manage_the_company(): void
    {
        $company = Company::factory()->onboardingPending()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->expectException(ModelNotFoundException::class);

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();
    }

    #[Test]
    public function it_leaves_the_catalogue_untouched_when_the_languages_change(): void
    {
        Queue::fake();

        $user = $this->owner();
        $service = Service::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Haircut']]);

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en', 'es'])->execute();
        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['es'])->execute();

        $this->assertEquals(['en' => 'Haircut'], $service->refresh()->name);
        $this->assertEquals(['es'], $service->missingLanguages());
    }

    #[Test]
    public function it_offers_the_catalogue_at_the_first_location(): void
    {
        Queue::fake();

        $user = $this->owner();
        new CreateDefaultCatalogue(company: $user->company)->execute();

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();

        $scranton = Location::query()->where('company_id', $user->company_id)->firstOrFail();

        $this->assertEquals(17, LocationService::query()->where('location_id', $scranton->id)->whereNull('price')->where('is_active', true)->count());
    }

    #[Test]
    public function it_does_not_offer_the_catalogue_twice_when_submitted_again(): void
    {
        Queue::fake();

        $user = $this->owner();
        new CreateDefaultCatalogue(company: $user->company)->execute();

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();
        new SetUpSalon(author: $user, name: 'Dunder Mifflin Scranton', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();

        $this->assertEquals(17, LocationService::query()->count());
    }

    #[Test]
    public function it_gets_the_catalogue_offered_when_it_is_created_after_the_first_location(): void
    {
        Queue::fake();

        $user = $this->owner();

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();
        new CreateDefaultCatalogue(company: $user->company)->execute();

        $scranton = Location::query()->where('company_id', $user->company_id)->firstOrFail();

        $this->assertEquals(17, LocationService::query()->where('location_id', $scranton->id)->count());
    }

    #[Test]
    public function it_does_not_offer_a_default_service_deleted_before_the_first_location(): void
    {
        Queue::fake();

        $user = $this->owner();
        new CreateDefaultCatalogue(company: $user->company)->execute();
        Service::query()->where('company_id', $user->company_id)->where('name->en', 'Balayage')->delete();

        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();

        $this->assertEquals(16, LocationService::query()->count());
    }

    #[Test]
    public function it_does_not_offer_the_catalogue_at_a_location_created_later(): void
    {
        Queue::fake();

        $user = $this->owner();
        new CreateDefaultCatalogue(company: $user->company)->execute();
        new SetUpSalon(author: $user, name: 'Dunder Mifflin', country: 'US', timezone: 'America/New_York', languages: ['en'])->execute();

        $stamford = new CreateLocation(author: $user, company: $user->company, name: 'Stamford')->execute();

        $this->assertEquals(0, LocationService::query()->where('location_id', $stamford->id)->count());
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'legal_name' => 'Dunder Mifflin Inc.',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}

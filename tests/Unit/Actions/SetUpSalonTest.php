<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetUpSalon;
use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
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

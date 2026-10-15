<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_services_step(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->get(route('onboarding.services.index'));

        $response->assertStatus(200);
        $response->assertSee('Onboarding · Step 2 of 4');
        $response->assertSee('What services do you offer?');
        $response->assertSee('Finish later');
        $response->assertSee(route('onboarding.services.update'));
    }

    #[Test]
    public function it_lists_the_categories_and_services_of_the_catalogue(): void
    {
        $user = $this->owner();
        $category = ServiceCategory::factory()->create(['company_id' => $user->company_id, 'name' => ['en' => 'Cuts']]);
        Service::factory()->create(['company_id' => $user->company_id, 'service_category_id' => $category->id, 'name' => ['en' => 'Dwight cut'], 'prices' => ['CAD' => 4250]]);

        $response = $this->actingAs($user)->get(route('onboarding.services.index'));

        $response->assertStatus(200);
        $response->assertSee('Cuts');
        $response->assertSee('Dwight cut');
        $response->assertSee('value="42.50"', false);
        $response->assertSee('1 service in your catalog');
    }

    #[Test]
    public function it_shows_the_empty_catalogue(): void
    {
        $user = $this->owner();

        $response = $this->actingAs($user)->get(route('onboarding.services.index'));

        $response->assertSee('Your catalog is empty');
        $response->assertSee('Use the example catalog');
    }

    #[Test]
    public function it_offers_every_service_at_the_headquarters_when_the_step_is_completed(): void
    {
        Queue::fake();

        $user = $this->owner();
        $scranton = Location::factory()->create(['company_id' => $user->company_id, 'is_headquarters' => true]);
        $service = Service::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)->put(route('onboarding.services.update'));

        $this->assertDatabaseHas('location_services', ['location_id' => $scranton->id, 'service_id' => $service->id]);
    }

    #[Test]
    public function it_completes_the_services_step(): void
    {
        Queue::fake();

        $user = $this->owner();

        $response = $this->actingAs($user)->put(route('onboarding.services.update'));

        $response->assertRedirect(route('onboarding.staff.index'));

        $this->assertEquals(OnboardingStepEnum::Staff, $user->company->refresh()->onboarding_step);
        $this->assertTrue($user->company->is_onboarding_pending);
    }

    #[Test]
    public function it_sends_a_user_who_is_not_the_owner_home(): void
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Services,
        ]);
        $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);

        $this->actingAs($pam)->get(route('onboarding.services.index'))->assertRedirect(route('home.index'));
        $this->actingAs($pam)->put(route('onboarding.services.update'))->assertRedirect(route('home.index'));
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Services,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        return $this->giveDefaultRole($user, Role::OWNER);
    }
}

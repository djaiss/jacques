<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
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
    public function it_adds_a_service_to_a_category_and_opens_it(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);

        $response = $this->actingAs($michael)->post(route('onboarding.services.services.create', $category));

        $service = Service::query()->where('company_id', $michael->company_id)->sole();

        $response->assertRedirect(route('onboarding.services.services.edit', $service));
        $this->assertEquals($category->id, $service->service_category_id);
        $this->assertEquals([], $service->prices);
        $this->assertEquals(30, $service->duration);
    }

    #[Test]
    public function it_shows_the_service_in_the_panel(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);

        $response = $this->actingAs($michael)->get(route('onboarding.services.services.edit', $service));

        $response->assertStatus(200);
        $response->assertSee('service-panel-title', false);
        $response->assertSee('Break it into steps');
    }

    #[Test]
    public function it_updates_a_service_with_its_steps(): void
    {
        Queue::fake();

        $michael = $this->owner(languages: ['en', 'fr'], currencies: ['CAD', 'USD']);
        $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
        $service = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Balayage']]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.services.update', $service), [
            'name' => ['en' => 'Balayage', 'fr' => 'Balayage'],
            'description' => ['en' => 'Sun kissed', 'fr' => ''],
            'category' => $colours->id,
            'prices' => ['CAD' => '180', 'USD' => ''],
            'steps' => [
                ['name' => ['en' => 'Application'], 'duration' => '45', 'requires_staff' => '1'],
                ['name' => ['en' => 'Processing'], 'duration' => '30', 'requires_staff' => '0'],
                ['name' => ['en' => 'Rinse'], 'duration' => '30', 'requires_staff' => '1'],
            ],
        ]);

        $response->assertRedirect(route('onboarding.services.services.edit', $service));

        $service->refresh();

        $this->assertEquals($colours->id, $service->service_category_id);
        $this->assertEquals(['CAD' => 18000], $service->prices);
        $this->assertEquals(['en' => 'Sun kissed'], $service->description);
        $this->assertEquals(105, $service->duration);
        $this->assertEquals(30, $service->free_duration);
    }

    #[Test]
    public function it_refuses_a_service_without_a_step_that_needs_a_team_member(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.services.update', $service), [
            'name' => ['en' => 'Processing only'],
            'steps' => [['duration' => '30', 'requires_staff' => '0']],
        ]);

        $response->assertRedirect(route('onboarding.services.services.edit', $service));
        $response->assertSessionHasErrors('service');
        $this->assertTrue($service->refresh()->steps->first()->requires_staff);
    }

    #[Test]
    public function it_refuses_a_step_shorter_than_a_minute(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.services.update', $service), [
            'steps' => [['duration' => '0', 'requires_staff' => '1']],
        ]);

        $response->assertRedirect(route('onboarding.services.services.edit', $service));
        $response->assertSessionHasErrors('steps.0.duration');
    }

    #[Test]
    public function it_offers_to_keep_a_service_without_a_category(): void
    {
        $michael = $this->owner();
        ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Hair Coloring']]);
        $service = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => null, 'name' => ['en' => 'Consultation']]);

        $response = $this->actingAs($michael)->get(route('onboarding.services.services.edit', $service));

        $response->assertSee('<option value="" selected>Without a category</option>', false);
    }

    #[Test]
    public function it_takes_a_service_out_of_its_category(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
        $service = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Balayage']]);

        $this->actingAs($michael)->put(route('onboarding.services.services.update', $service), [
            'name' => ['en' => 'Balayage'],
            'category' => '',
            'steps' => [['duration' => '30', 'requires_staff' => '1']],
        ]);

        $this->assertNull($service->refresh()->service_category_id);
    }

    #[Test]
    public function it_removes_a_service_and_keeps_it_for_undo(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Root Touch-up']]);

        $response = $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $service));

        $response->assertRedirect(route('onboarding.services.index'));
        $response->assertSessionHas('onboarding.removal.message', '“Root Touch-up” removed.');
        $this->assertModelMissing($service);
    }

    #[Test]
    public function it_returns_not_found_for_a_service_or_a_category_of_another_company(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create();
        $mine = Service::factory()->create(['company_id' => $michael->company_id]);
        $category = ServiceCategory::factory()->create();

        $this->actingAs($michael)->get(route('onboarding.services.services.edit', $service))->assertNotFound();
        $this->actingAs($michael)->put(route('onboarding.services.services.update', $service), ['steps' => [['duration' => '30', 'requires_staff' => '1']]])->assertNotFound();
        $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $service))->assertNotFound();
        $this->actingAs($michael)->post(route('onboarding.services.services.create', $category))->assertNotFound();
        $this->actingAs($michael)->put(route('onboarding.services.services.update', $mine), ['category' => $category->id, 'steps' => [['duration' => '30', 'requires_staff' => '1']]])->assertNotFound();
        $this->assertModelExists($service);
    }

    /**
     * @param  list<string>  $languages
     * @param  list<string>  $currencies
     */
    private function owner(array $languages = ['en'], array $currencies = ['CAD']): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Services,
            'languages' => $languages,
            'currencies' => $currencies,
        ]);

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    }
}

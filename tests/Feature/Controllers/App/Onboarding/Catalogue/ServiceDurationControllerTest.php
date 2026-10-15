<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceDurationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_duration_of_a_one_step_service(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => '45']);

        $response->assertRedirect(route('onboarding.services.index'));
        $response->assertSessionHas('catalogue_saved', true);
        $this->assertEquals(45, $service->refresh()->duration);
    }

    #[Test]
    public function it_refuses_a_duration_that_is_not_a_number(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => 'long']);

        $response->assertSessionHasErrors('duration', errorBag: "service_{$service->id}");
        $this->assertEquals(30, $service->refresh()->duration);
    }

    #[Test]
    public function it_refuses_to_change_the_duration_of_a_service_made_of_steps(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);
        ServiceStep::factory()->create(['service_id' => $service->id, 'position' => 2, 'requires_staff' => false]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => '45']);

        $response->assertSessionHasErrors('service', errorBag: "service_{$service->id}");
        $this->assertEquals(60, $service->refresh()->duration);
    }

    #[Test]
    public function it_returns_not_found_for_a_service_of_another_company(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create();

        $this->actingAs($michael)->put(route('onboarding.services.serviceDurations.update', $service), ['duration' => '45'])->assertNotFound();
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Services,
        ]);

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    }
}

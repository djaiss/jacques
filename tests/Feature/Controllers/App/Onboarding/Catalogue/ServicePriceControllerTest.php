<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServicePriceControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_the_prices_in_cents_and_removes_an_empty_one(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id, 'prices' => ['CAD' => 3500, 'USD' => 2600]]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), [
            'prices' => ['CAD' => '42,50', 'USD' => ''],
        ]);

        $response->assertRedirect(route('onboarding.services.index'));
        $this->assertEquals(['CAD' => 4250], $service->refresh()->prices);
        $this->assertEquals(['USD'], $service->missingCurrencies());
    }

    #[Test]
    public function it_keeps_a_free_service_free(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '0', 'USD' => '0']]);

        $this->assertEquals(['CAD' => 0, 'USD' => 0], $service->refresh()->prices);
    }

    #[Test]
    public function it_refuses_a_negative_price_or_too_many_decimals(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '-5']])
            ->assertSessionHasErrors('prices.CAD', errorBag: "service_{$service->id}");
        $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '12.345']])
            ->assertSessionHasErrors('prices.CAD', errorBag: "service_{$service->id}");
        $this->assertEquals(['CAD' => 3500], $service->refresh()->prices);
    }

    #[Test]
    public function it_returns_not_found_for_a_service_of_another_company(): void
    {
        $michael = $this->owner();
        $service = Service::factory()->create();

        $this->actingAs($michael)->put(route('onboarding.services.servicePrices.update', $service), ['prices' => ['CAD' => '10']])->assertNotFound();
    }

    private function owner(): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Services,
            'currencies' => ['CAD', 'USD'],
        ]);

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    }
}

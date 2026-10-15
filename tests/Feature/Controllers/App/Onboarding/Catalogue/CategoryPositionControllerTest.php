<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Onboarding\Catalogue;

use App\Enums\OnboardingStepEnum;
use App\Models\Company;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_category_up_and_down(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 1]);
        $barbering = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 2]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.categoryPositions.update', $barbering), ['direction' => 'up']);

        $response->assertRedirect(route('onboarding.services.index'));
        $this->assertEquals(1, $barbering->refresh()->position);
        $this->assertEquals(2, $cuts->refresh()->position);

        $this->actingAs($michael)->put(route('onboarding.services.categoryPositions.update', $barbering), ['direction' => 'down']);

        $this->assertEquals(2, $barbering->refresh()->position);
    }

    #[Test]
    public function it_refuses_to_move_the_first_category_up(): void
    {
        $michael = $this->owner();
        $cuts = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'position' => 1]);

        $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.categoryPositions.update', $cuts), ['direction' => 'up']);

        $response->assertSessionHas('error');
        $this->assertEquals(1, $cuts->refresh()->position);
    }

    #[Test]
    public function it_returns_not_found_for_a_category_of_another_company(): void
    {
        $michael = $this->owner();
        $category = ServiceCategory::factory()->create(['position' => 2]);

        $this->actingAs($michael)->put(route('onboarding.services.categoryPositions.update', $category), ['direction' => 'up'])->assertNotFound();
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

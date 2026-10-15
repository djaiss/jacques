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

class CatalogueControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_empties_the_catalogue_and_keeps_it_for_undo(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
        Service::factory()->count(2)->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id]);

        $response = $this->actingAs($michael)->delete(route('onboarding.services.catalogue.destroy'));

        $response->assertRedirect(route('onboarding.services.index'));
        $response->assertSessionHas('onboarding.removal.message', 'The catalog was emptied.');
        $this->assertEquals(0, Service::query()->where('company_id', $michael->company_id)->count());
        $this->assertEquals(0, ServiceCategory::query()->where('company_id', $michael->company_id)->count());
    }

    #[Test]
    public function it_brings_back_the_example_catalogue_into_an_empty_one(): void
    {
        Queue::fake();

        $michael = $this->owner();

        $response = $this->actingAs($michael)->post(route('onboarding.services.catalogue.create'));

        $response->assertRedirect(route('onboarding.services.index'));
        $this->assertEquals(17, Service::query()->where('company_id', $michael->company_id)->count());
    }

    #[Test]
    public function it_refuses_the_example_catalogue_when_the_catalogue_is_not_empty(): void
    {
        $michael = $this->owner();
        Service::factory()->create(['company_id' => $michael->company_id]);

        $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->post(route('onboarding.services.catalogue.create'));

        $response->assertSessionHas('error');
        $this->assertEquals(1, Service::query()->where('company_id', $michael->company_id)->count());
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

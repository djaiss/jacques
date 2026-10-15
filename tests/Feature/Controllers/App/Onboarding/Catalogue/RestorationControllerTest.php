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

class RestorationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_back_a_removed_service_in_its_place(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id]);
        Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Full Color'], 'position' => 1]);
        $touchUp = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Root Touch-up'], 'position' => 2]);
        Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $colours->id, 'name' => ['en' => 'Balayage'], 'position' => 3]);

        $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $touchUp));
        $response = $this->actingAs($michael)->post(route('onboarding.services.restoration.create'));

        $response->assertRedirect(route('onboarding.services.index'));
        $response->assertSessionMissing('onboarding.removal');
        $this->assertEquals(['Full Color', 'Root Touch-up', 'Balayage'], $colours->services()->get()->map(fn (Service $service): string => $service->name['en'])->all());
    }

    #[Test]
    public function it_puts_back_an_emptied_catalogue(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts']]);
        Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id]);

        $this->actingAs($michael)->delete(route('onboarding.services.catalogue.destroy'));
        $this->actingAs($michael)->post(route('onboarding.services.restoration.create'));

        $this->assertEquals(['en' => 'Cuts'], ServiceCategory::query()->where('company_id', $michael->company_id)->sole()->name);
        $this->assertEquals(1, Service::query()->where('company_id', $michael->company_id)->count());
    }

    #[Test]
    public function it_forgets_the_removal_after_another_change(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $service = Service::factory()->create(['company_id' => $michael->company_id]);

        $this->actingAs($michael)->delete(route('onboarding.services.services.destroy', $service));
        $this->actingAs($michael)->post(route('onboarding.services.categories.create'));
        $response = $this->actingAs($michael)->post(route('onboarding.services.restoration.create'));

        $response->assertSessionHas('error');
        $this->assertEquals(0, Service::query()->where('company_id', $michael->company_id)->count());
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

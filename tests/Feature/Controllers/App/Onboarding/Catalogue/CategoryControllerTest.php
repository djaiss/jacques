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

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_unnamed_category(): void
    {
        Queue::fake();

        $michael = $this->owner();

        $response = $this->actingAs($michael)->post(route('onboarding.services.categories.create'));

        $response->assertRedirect(route('onboarding.services.index'));
        $response->assertSessionHas('catalogue_saved', true);

        $this->assertEquals([], ServiceCategory::query()->where('company_id', $michael->company_id)->sole()->name);
    }

    #[Test]
    public function it_renames_a_category_in_every_language(): void
    {
        Queue::fake();

        $michael = $this->owner(languages: ['en', 'fr']);
        $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts']]);

        $response = $this->actingAs($michael)->put(route('onboarding.services.categories.update', $category), [
            'name' => ['en' => 'Haircuts', 'fr' => 'Coupes'],
        ]);

        $response->assertRedirect(route('onboarding.services.index'));
        $this->assertEquals(['en' => 'Haircuts', 'fr' => 'Coupes'], $category->refresh()->name);
    }

    #[Test]
    public function it_refuses_a_name_another_category_has(): void
    {
        $michael = $this->owner();
        ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Cuts'], 'position' => 1]);
        $colours = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Colours'], 'position' => 2]);

        $response = $this->actingAs($michael)->from(route('onboarding.services.index'))->put(route('onboarding.services.categories.update', $colours), [
            'name' => ['en' => 'Cuts'],
        ]);

        $response->assertSessionHasErrors('category', errorBag: "category_{$colours->id}");
        $this->assertEquals(['en' => 'Colours'], $colours->refresh()->name);
    }

    #[Test]
    public function it_removes_a_category_with_its_services_and_keeps_it_for_undo(): void
    {
        Queue::fake();

        $michael = $this->owner();
        $category = ServiceCategory::factory()->create(['company_id' => $michael->company_id, 'name' => ['en' => 'Barbering']]);
        $shave = Service::factory()->create(['company_id' => $michael->company_id, 'service_category_id' => $category->id]);

        $response = $this->actingAs($michael)->delete(route('onboarding.services.categories.destroy', $category));

        $response->assertRedirect(route('onboarding.services.index'));
        $response->assertSessionHas('onboarding.removal.message', '“Barbering” and its 1 service removed.');
        $this->assertModelMissing($category);
        $this->assertModelMissing($shave);
    }

    #[Test]
    public function it_returns_not_found_for_a_category_of_another_company(): void
    {
        $michael = $this->owner();
        $category = ServiceCategory::factory()->create();

        $this->actingAs($michael)->put(route('onboarding.services.categories.update', $category), ['name' => ['en' => 'Cuts']])->assertNotFound();
        $this->actingAs($michael)->delete(route('onboarding.services.categories.destroy', $category))->assertNotFound();
        $this->assertModelExists($category);
    }

    /**
     * @param  list<string>  $languages
     */
    private function owner(array $languages = ['en']): User
    {
        $company = Company::factory()->onboardingPending()->create([
            'name' => 'Dunder Mifflin',
            'onboarding_step' => OnboardingStepEnum::Services,
            'languages' => $languages,
        ]);

        return $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
    }
}

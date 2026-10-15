<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_a_service_at_a_location_with_its_own_price(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
        $haircut = Service::factory()->create(['company_id' => $company->id, 'name' => ['en' => 'Haircut'], 'price' => 3500]);

        $locationService = new CreateLocationService(author: $michael, location: $scranton, service: $haircut, price: 4000)->execute();

        $this->assertInstanceOf(LocationService::class, $locationService);
        $this->assertDatabaseHas('location_services', [
            'id' => $locationService->id,
            'location_id' => $scranton->id,
            'service_id' => $haircut->id,
            'price' => 4000,
            'is_active' => true,
        ]);
        $this->assertEquals(4000, $locationService->resolvedPrice());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationServiceCreated
                && $job->parameters === ['name' => 'Haircut', 'location' => 'Scranton'],
        );
    }

    #[Test]
    public function it_lets_a_location_follow_the_price_of_the_service(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $haircut = Service::factory()->create(['company_id' => $company->id, 'price' => 3500]);

        $locationService = new CreateLocationService(author: $michael, location: $stamford, service: $haircut)->execute();

        $haircut->update(['price' => 3800]);

        $this->assertNull($locationService->price);
        $this->assertEquals(3800, $locationService->refresh()->resolvedPrice());
    }

    #[Test]
    public function it_lets_a_manager_offer_a_service_at_their_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $dwight->locations()->attach($scranton->id);
        $haircut = Service::factory()->create(['company_id' => $company->id]);

        new CreateLocationService(author: $dwight, location: $scranton, service: $haircut)->execute();

        $this->assertDatabaseHas('location_services', ['location_id' => $scranton->id, 'service_id' => $haircut->id]);
    }

    #[Test]
    public function it_throws_when_a_manager_offers_a_service_at_another_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $dwight->locations()->attach($scranton->id);
        $haircut = Service::factory()->create(['company_id' => $company->id]);

        new CreateLocationService(author: $dwight, location: $stamford, service: $haircut)->execute();
    }

    #[Test]
    public function it_throws_when_a_receptionist_offers_a_service(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $pam = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::RECEPTIONIST);
        $pam->locations()->attach($scranton->id);
        $haircut = Service::factory()->create(['company_id' => $company->id]);

        new CreateLocationService(author: $pam, location: $scranton, service: $haircut)->execute();
    }

    #[Test]
    public function it_throws_when_the_service_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);

        new CreateLocationService(author: $michael, location: $scranton, service: Service::factory()->create())->execute();
    }

    #[Test]
    public function it_throws_when_the_service_is_deactivated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $haircut = Service::factory()->inactive()->create(['company_id' => $company->id]);

        new CreateLocationService(author: $michael, location: $scranton, service: $haircut)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_already_offers_the_service(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new CreateLocationService(author: $michael, location: $locationService->location, service: $locationService->service)->execute();
    }

    #[Test]
    public function it_throws_when_the_price_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $company = Company::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::ServiceManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $haircut = Service::factory()->create(['company_id' => $company->id]);

        new CreateLocationService(author: $michael, location: $scranton, service: $haircut, price: -100)->execute();
    }
}

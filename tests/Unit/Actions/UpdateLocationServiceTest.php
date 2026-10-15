<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateLocationService;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\LocationService;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_price_and_stops_offering_the_service_at_one_location_only(): void
    {
        Queue::fake();

        $scranton = Location::factory()->create(['name' => 'Scranton']);
        $atScranton = LocationService::factory()->create(['location_id' => $scranton->id]);
        $stamford = Location::factory()->create(['company_id' => $scranton->company_id]);
        $atStamford = LocationService::factory()->create(['location_id' => $stamford->id, 'service_id' => $atScranton->service_id]);
        $michael = $this->grant(User::factory()->create(['company_id' => $scranton->company_id]), PermissionEnum::ServiceManage);

        new UpdateLocationService(author: $michael, locationService: $atScranton, prices: ['CAD' => 4000], isActive: false)->execute();

        $this->assertEquals(['CAD' => 4000], $atScranton->refresh()->prices);
        $this->assertFalse($atScranton->is_active);
        $this->assertTrue($atStamford->refresh()->is_active);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationServiceUpdated
                && $job->parameters === ['name' => $atScranton->service->name['en'], 'location' => 'Scranton'],
        );
    }

    #[Test]
    public function it_clears_the_price_so_the_location_follows_the_service_again(): void
    {
        Queue::fake();

        $locationService = LocationService::factory()->create(['prices' => ['CAD' => 4000]]);
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new UpdateLocationService(author: $michael, locationService: $locationService, prices: [])->execute();

        $this->assertNull($locationService->refresh()->prices);
        $this->assertEquals($locationService->service->prices['CAD'], $locationService->resolvedPrice('CAD'));
    }

    #[Test]
    public function it_throws_when_a_price_is_in_a_currency_the_company_does_not_use(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('USD is not a currency of the company');

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new UpdateLocationService(author: $michael, locationService: $locationService, prices: ['USD' => 4000])->execute();
    }

    #[Test]
    public function it_throws_when_the_price_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locationService = LocationService::factory()->create();
        $michael = $this->grant(User::factory()->create(['company_id' => $locationService->location->company_id]), PermissionEnum::ServiceManage);

        new UpdateLocationService(author: $michael, locationService: $locationService, prices: ['CAD' => -1])->execute();
    }

    #[Test]
    public function it_throws_when_a_manager_changes_another_location(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $atStamford = LocationService::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $atStamford->location->company_id]);
        $dwight = $this->giveDefaultRole(User::factory()->create(['company_id' => $scranton->company_id]), Role::MANAGER);
        Staff::factory()->create(['company_id' => $dwight->company_id, 'user_id' => $dwight->id])->locations()->attach($scranton->id);

        new UpdateLocationService(author: $dwight, locationService: $atStamford, prices: ['CAD' => 4000])->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $michael = $this->grant(User::factory()->create(), PermissionEnum::ServiceManage);

        new UpdateLocationService(author: $michael, locationService: LocationService::factory()->create(), prices: ['CAD' => 4000])->execute();
    }
}

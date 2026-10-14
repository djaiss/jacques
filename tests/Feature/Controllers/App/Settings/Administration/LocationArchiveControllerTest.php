<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationArchiveControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_archives_an_location(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $location = Location::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index'))
            ->post(route('settings.locationArchives.create', $location->id));

        $response->assertRedirect(route('settings.locations.index'));
        $response->assertSessionHas('status', 'The location is archived.');

        $this->assertTrue($location->refresh()->isArchived());
    }

    #[Test]
    public function it_reopens_an_location(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $location = Location::factory()->archived()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user)
            ->from(route('settings.locations.index', 'archived'))
            ->delete(route('settings.locationArchives.destroy', $location->id));

        $response->assertRedirect(route('settings.locations.index', 'archived'));
        $response->assertSessionHas('status', 'The location is open again.');

        $this->assertFalse($location->refresh()->isArchived());
    }

    #[Test]
    public function it_cannot_archive_an_location_of_another_company(): void
    {
        $user = $this->administrator();
        $location = Location::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.locationArchives.create', $location->id))
            ->assertNotFound();
    }

    #[Test]
    public function it_stops_somebody_who_may_not_administer_the_company_from_archiving_an_location(): void
    {
        $company = Company::factory()->create();
        $user = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::STAFF);
        $location = Location::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->post(route('settings.locationArchives.create', $location->id))
            ->assertNotFound();

        $this->assertFalse($location->refresh()->isArchived());
    }

    #[Test]
    public function it_goes_back_to_the_list_it_was_asked_from(): void
    {
        Queue::fake();

        $user = $this->administrator();
        $location = Location::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)
            ->from(route('settings.locations.index', 'all'))
            ->post(route('settings.locationArchives.create', $location->id))
            ->assertRedirect(route('settings.locations.index', 'all'));
    }

    private function administrator(): User
    {
        $company = Company::factory()->create();

        return $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
    }
}

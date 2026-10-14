<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ArchiveLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchiveLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_archives_an_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
        $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Utica branch']);

        $archived = new ArchiveLocation(author: $author, location: $location)->execute();

        $this->assertInstanceOf(Location::class, $archived);

        $location->refresh();

        $this->assertTrue($location->isArchived());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationArchived
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Utica branch'],
        );
    }

    #[Test]
    public function it_takes_the_headquarters_badge_away(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
        $location = Location::factory()->primary()->create(['company_id' => $company->id]);

        new ArchiveLocation(author: $author, location: $location)->execute();

        $location->refresh();

        $this->assertFalse($location->is_headquarters);
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $location = Location::factory()->create(['company_id' => $company->id]);

        new ArchiveLocation(author: $author, location: $location)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);
        $location = Location::factory()->create();

        new ArchiveLocation(author: $author, location: $location)->execute();
    }

    #[Test]
    public function it_lets_a_manager_archive_only_the_locations_they_are_assigned_to(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stamford = Location::factory()->create(['company_id' => $company->id]);
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $michael->locations()->attach($scranton);

        new ArchiveLocation(author: $michael, location: $scranton)->execute();

        $this->assertTrue($scranton->refresh()->isArchived());

        $this->expectException(ModelNotFoundException::class);

        new ArchiveLocation(author: $michael, location: $stamford)->execute();
    }
}

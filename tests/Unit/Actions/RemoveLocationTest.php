<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveLocation;
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

class RemoveLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_somebody_off_a_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton']);
        $dwight = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);
        $dwight->locations()->attach($scranton);

        $result = new RemoveLocation(author: $author, user: $dwight, location: $scranton)->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseMissing('user_locations', [
            'user_id' => $dwight->id,
            'location_id' => $scranton->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserRemovedFromLocation
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === [
                    'name' => 'Scranton',
                    'email' => 'dwight.schrute@dundermifflin.com',
                ],
        );
    }

    #[Test]
    public function it_throws_when_the_author_may_not_hand_out_roles(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $michael = $this->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::MANAGER);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $dwight = User::factory()->create(['company_id' => $company->id]);

        new RemoveLocation(author: $michael, user: $dwight, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $scranton = Location::factory()->create(['company_id' => $company->id]);
        $stranger = User::factory()->create();

        new RemoveLocation(author: $author, user: $stranger, location: $scranton)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::RoleManage);
        $nashua = Location::factory()->create();
        $dwight = User::factory()->create(['company_id' => $company->id]);

        new RemoveLocation(author: $author, user: $dwight, location: $nashua)->execute();
    }
}

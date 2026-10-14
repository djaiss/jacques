<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyLocation;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_an_location(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $author = $this->grant(User::factory()->create(['company_id' => $company->id]), PermissionEnum::LocationManage);
        $location = Location::factory()->create(['company_id' => $company->id, 'name' => 'Scranton branch']);

        new DestroyLocation(author: $author, location: $location)->execute();

        $this->assertModelMissing($location);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationDeleted
                && $job->company->id === $company->id
                && $job->user->id === $author->id
                && $job->parameters === ['name' => 'Scranton branch'],
        );
    }

    #[Test]
    public function it_throws_when_the_author_may_not_change_the_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = User::factory()->create(['company_id' => $company->id]);
        $location = Location::factory()->create(['company_id' => $company->id]);

        new DestroyLocation(author: $author, location: $location)->execute();
    }

    #[Test]
    public function it_throws_when_the_location_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = $this->grant(User::factory()->create(), PermissionEnum::LocationManage);
        $location = Location::factory()->create();

        new DestroyLocation(author: $author, location: $location)->execute();
    }
}
